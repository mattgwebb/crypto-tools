<?php


namespace App\Model;


use App\Entity\Algorithm\AlgoTestResult;
use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\Candle;
use App\Entity\Algorithm\StrategyResult;
use App\Entity\Data\Currency;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\ExternalIndicatorData;
use App\Entity\Data\ExternalIndicatorDataType;
use App\Entity\Data\TimeFrames;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeTypes;
use App\Repository\Algorithm\BotAlgorithmRepository;
use App\Repository\Data\CandleRepository;
use App\Repository\Data\CurrencyPairRepository;
use App\Repository\Data\ExternalIndicatorDataRepository;
use App\Repository\Trade\TradeRepository;
use App\Service\TechnicalAnalysis\Strategies;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;

class BotAlgorithmManager
{
    /**
     * Number of candles passed on to the strategy calculations
     */
    const CANDLES_TO_LOAD = 201;

    /**
     * @var BotAlgorithmRepository
     */
    private $botAlgorithmRepo;

    /**
     * @var CurrencyPairRepository
     */
    private $currencyPairRepo;

    /**
     * @var CandleRepository
     */
    private $candleRepository;

    /**
     * @var Strategies
     */
    private $strategies;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TradeRepository
     */
    private $tradeRepository;

    /**
     * @var ExternalIndicatorDataRepository
     */
    private $externalIndicatorRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * BotAlgorithmManager constructor.
     * @param BotAlgorithmRepository $botAlgorithmRepo
     * @param CurrencyPairRepository $currencyRepo
     * @param CandleRepository $candleRepository
     * @param Strategies $strategies
     * @param LoggerInterface $algosLogger
     * @param TradeRepository $tradeRepository
     * @param ExternalIndicatorDataRepository $externalIndicatorRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(BotAlgorithmRepository $botAlgorithmRepo, CurrencyPairRepository $currencyRepo,
                                CandleRepository $candleRepository, Strategies $strategies, LoggerInterface $algosLogger,
                                TradeRepository $tradeRepository, ExternalIndicatorDataRepository $externalIndicatorRepository,
                                EntityManagerInterface $entityManager)
    {
        $this->botAlgorithmRepo = $botAlgorithmRepo;
        $this->currencyPairRepo = $currencyRepo;
        $this->candleRepository = $candleRepository;
        $this->strategies = $strategies;
        $this->logger = $algosLogger;
        $this->tradeRepository = $tradeRepository;
        $this->entityManager = $entityManager;
        $this->externalIndicatorRepository = $externalIndicatorRepository;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @return array
     * @throws \Exception
     */
    public function runTest(BotAlgorithm $algo, int $from = 0, int $to = 0,
                            int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        $this->logger->info("********************* New test  ************************");
        $this->logger->info(json_encode($algo));

        $initialFrom = $from;

        $lastPositionCandles = $candlesToLoad - 1;

        $from -= $lastPositionCandles * ($algo->getTimeFrame() * 60);
        $candles = $this->currencyPairRepo->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame(), $from, $to);

        $openTradePrice = 0;
        $compoundedProfit = 1;

        $trades = [];

        for($i=$lastPositionCandles; $i < count($candles); $i++) {
            $auxData = array_slice($candles, $i - $lastPositionCandles, $candlesToLoad);
            /** TODO delete candles from array after using them
             * TODO for some reason it doesn´t calculate indicators properly
             */
            //array_shift($candles);

            $currentCandle = $auxData[count($auxData) - 1];

            $currentTradeStatus = $openTradePrice > 0 ? TradeTypes::TRADE_BUY : TradeTypes::TRADE_SELL;

            $this->strategies->setData($auxData);
            $result = $this->strategies->runStrategies($algo, $currentTradeStatus);

            if($currentTradeStatus == TradeTypes::TRADE_BUY) {
                if($algo->getStopLoss()) {
                    $stopLoss = $this->strategies->stopLosses($openTradePrice, $algo->getStopLoss())->isShort();
                } else {
                    $stopLoss = false;
                }
                if($algo->getTakeProfit()) {
                    $takeProfit = $this->strategies->takeProfit($openTradePrice, $algo->getTakeProfit())->isShort();
                } else {
                    $takeProfit = false;
                }

                $short = $stopLoss || $takeProfit;
            } else {
                $short = false;
            }

            if(($result->isLong()) && $currentTradeStatus == TradeTypes::TRADE_SELL) {
                $openTradePrice = $currentCandle->getClosePrice();

                $trade = $this->newTestTrade($currentCandle, TradeTypes::TRADE_BUY);
                $trade['extra_data'] = $result->getExtraData();

                $trades[] = $trade;

                $this->logger->info(json_encode($trade));
            }
            if(($result->isShort() || $short) && $currentTradeStatus == TradeTypes::TRADE_BUY) {
                $profit = ($currentCandle->getClosePrice()/$openTradePrice);
                $percentage = ($profit - 1) * 100;
                $compoundedProfit *= $profit;

                $trade = $this->newTestTrade($currentCandle, TradeTypes::TRADE_SELL);
                $trade['extra_data'] = $result->getExtraData();
                $trade["percentage"] = round($percentage, 2);
                $trade["stopLoss_takeProfit"] = $short;
                $trade['invalidation'] = $result->isFromInvalidation();
                $trades[] = $trade;

                $this->logger->info(json_encode($trade));

                $openTradePrice = 0;
            }
        }

        $compoundedPercentage = ($compoundedProfit  - 1) * 100;

        if(isset($currentCandle)) {
            $this->saveAlgoTestResult($algo, $compoundedPercentage, count($trades), $initialFrom, $currentCandle->getCloseTime());
        }

        $trade = "percentage $compoundedPercentage";
        //$trades[] = $trade;
        $this->logger->info($trade);

        return $trades;
    }


    /**
     * @param ExternalIndicatorDataType $type
     * @param CurrencyPair $pair
     * @param int $from
     * @param int $to
     * @return array
     */
    public function runExternalIndicatorTest(ExternalIndicatorDataType $type, CurrencyPair $pair, int $from, int $to)
    {
        $openTradePrice = 0;
        $compoundedProfit = 1;
        $trades = [];

        $this->logger->info("********************* New test  ************************");
        $this->logger->info($type->getName()." ".$pair->getSymbol(). "from $from to $to");

        $data = $this->externalIndicatorRepository->getData($type, $from, $to);

        /** @var ExternalIndicatorData $indicatorData */
        foreach($data as $indicatorData) {
            if($openTradePrice == 0 && $indicatorData->getIndicatorValue() <= 20) {
                $trade = $this->newExternalIndicatorTrade($pair, $indicatorData, TradeTypes::TRADE_BUY);
                $trades[] = $trade;

                $openTradePrice = $trade['price'];

                $this->logger->info(json_encode($trade));
            } else if($openTradePrice > 0 && $indicatorData->getIndicatorValue() >= 60) {

                $trade = $this->newExternalIndicatorTrade($pair, $indicatorData, TradeTypes::TRADE_SELL);

                $profit = ($trade['price']/$openTradePrice);
                $percentage = ($profit - 1) * 100;
                $compoundedProfit *= $profit;

                $trade["percentage"] = round($percentage, 2);
                $trade["stopLoss_takeProfit"] = false;
                $trades[] = $trade;

                $openTradePrice = 0;

                $this->logger->info(json_encode($trade));
            }
        }
        $compoundedPercentage = ($compoundedProfit  - 1) * 100;

        $trade = "percentage $compoundedPercentage";
        $this->logger->info($trade);

        return $trades;
    }

    /**
     * @param CurrencyPair $pair
     * @param int $timeFrame
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @return array
     */
    public function runTrendLinesTest(CurrencyPair $pair, int $timeFrame = 60, int $from = 0, int $to = 0,
                            int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        $lastPositionCandles = $candlesToLoad - 1;

        $from -= $lastPositionCandles * ($timeFrame * 60);
        $candles = $this->currencyPairRepo->getCandlesByTimeFrame($pair, $timeFrame, $from, $to);

        $this->strategies->setData($candles);
        return $this->strategies->detectTrendLines();
    }

    /**
     * @param BotAlgorithm $algo
     * @param Candle[] $candles
     * @return StrategyResult|bool
     */
    public function runAlgo(BotAlgorithm $algo, $candles)
    {
        $this->strategies->setData($candles);
        return $this->strategies->runStrategies($algo);
    }

    /**
     * @param BotAlgorithm $algo
     * @param float $currentPrice
     * @return StrategyResult
     */
    public function checkStopLossAndTakeProfit(BotAlgorithm $algo, float $currentPrice)
    {
        $result = new StrategyResult();

        try {
            $lastPrice = $this->tradeRepository->getAlgoLastBuyTradePrice($algo);
        } catch (\Exception $e) {
            return $result;
        }

        if(!$lastPrice) {
            return $result;
        }

        if($algo->isLong() ) {
            $this->strategies->setCurrentPrice($currentPrice);

            if($algo->getStopLoss() != 0) {
                $result = $this->strategies->stopLosses($lastPrice, $algo->getStopLoss());
            }
            if($result->isShort() && $algo->getTakeProfit() != 0) {
                $result = $this->strategies->takeProfit($lastPrice, $algo->getTakeProfit());
            }
        }
        return $result;
    }

    /**
     * @param int $id
     * @return BotAlgorithm|null
     */
    public function getAlgo(int $id)
    {
        return $this->botAlgorithmRepo->find($id);
    }

    /**
     * @param BotAlgorithm $algo
     */
    public function saveAlgo(BotAlgorithm $algo)
    {
        $this->entityManager->persist($algo);
    }

    /**
     * @param CurrencyPair $pair
     * @param ExternalIndicatorData $indicatorData
     * @param int $tradeSide
     * @return array
     */
    private function newExternalIndicatorTrade(CurrencyPair $pair, ExternalIndicatorData $indicatorData, int $tradeSide)
    {
        $candle = $this->candleRepository->getCandleByTime($pair, $indicatorData->getCloseTime());

        return $this->newTestTrade($candle, $tradeSide);
    }

    /**
     * @param Candle $currentCandle
     * @param int $tradeSide
     * @return array
     * @throws \Exception
     */
    private function newTestTrade(Candle $currentCandle, int $tradeSide)
    {
        $date = new \DateTime('@' .$currentCandle->getCloseTime());
        $date->setTimezone(new \DateTimeZone("Europe/Madrid"));

        $trade = [
            "time" => date_format($date, 'D M j G:i:s'),
            "timestamp"=> $currentCandle->getCloseTime() * 1000,
            "price" => $currentCandle->getClosePrice()
        ];

        if($tradeSide == TradeTypes::TRADE_BUY) {
            $trade["trade"] = "long";
        } else if($tradeSide == TradeTypes::TRADE_SELL) {
            $trade["trade"] = "short";
        }
        return $trade;
    }

    /**
     * @param BotAlgorithm $algo
     * @param float $percentage
     * @param int $numTrades
     * @param int $startTime
     * @param int $finishTime
     */
    private function saveAlgoTestResult(BotAlgorithm $algo, float $percentage, int $numTrades, int $startTime, int $finishTime)
    {
        $testResult = new AlgoTestResult();
        $testResult->setAlgo($algo);
        $testResult->setPercentage($percentage);
        $testResult->setTimestamp(time());
        $testResult->setTrades($numTrades);
        $testResult->setStartTime($startTime);
        $testResult->setEndTime($finishTime);
        $testResult->setTimeFrame($algo->getTimeFrame());

        $extra = [
            "entry_strategies" => $algo->getEntryStrategyCombination(),
            "exit_strategies" => $algo->getExitStrategyCombination()
        ];
        $testResult->setObservations(json_encode($extra));

        $this->entityManager->persist($testResult);
        $this->entityManager->flush();
    }
}