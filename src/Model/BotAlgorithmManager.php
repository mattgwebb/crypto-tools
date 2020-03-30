<?php


namespace App\Model;


use App\Entity\Algorithm\AlgoTestResult;
use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\Candle;
use App\Entity\Algorithm\StrategyResult;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeTypes;
use App\Repository\BotAlgorithmRepository;
use App\Repository\CurrencyPairRepository;
use App\Repository\TradeRepository;
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
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * BotAlgorithmManager constructor.
     * @param BotAlgorithmRepository $botAlgorithmRepo
     * @param CurrencyPairRepository $currencyRepo
     * @param Strategies $strategies
     * @param LoggerInterface $algosLogger
     * @param TradeRepository $tradeRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(BotAlgorithmRepository $botAlgorithmRepo, CurrencyPairRepository $currencyRepo,
                                Strategies $strategies, LoggerInterface $algosLogger, TradeRepository $tradeRepository,
                                EntityManagerInterface $entityManager)
    {
        $this->botAlgorithmRepo = $botAlgorithmRepo;
        $this->currencyPairRepo = $currencyRepo;
        $this->strategies = $strategies;
        $this->logger = $algosLogger;
        $this->tradeRepository = $tradeRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @return array
     */
    public function runDivergenceTest(BotAlgorithm $algo, int $from = 0, int $to = 0, int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        $this->logger->info("********************* New test  ************************");
        $this->logger->info(json_encode($algo));

        $initialFrom = $from;

        $lastPositionCandles = $candlesToLoad - 1;

        $from -= $lastPositionCandles * ($algo->getTimeFrame() * 60);
        $candles = $this->currencyPairRepo->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame(), $from, $to);

        $openTradePrice = 0;
        $totalPercentage = 0;

        $initialInvestment = 1000;

        $trades = $divergences = [];

        for($i=$lastPositionCandles; $i < count($candles); $i++) {
            $auxData = array_slice($candles, $i - $lastPositionCandles, $candlesToLoad);
            /** TODO delete candles from array after using them
             * TODO for some reason it doesn´t calculate indicators properly
             */
            //array_shift($candles);

            $currentCandle = $auxData[count($auxData) - 1];

            $this->strategies->setData($auxData);
            $result = $this->strategies->runStrategy($algo);

            /*if(!$result->noTrade()) {
                $divergences[] = $result->getExtraData()['divergence_line'];
            }*/

            if(($result->isLong()) && $openTradePrice == 0) {
                $openTradePrice = $currentCandle->getClosePrice();

                $trade = $this->newTestTrade($currentCandle, TradeTypes::TRADE_BUY);
                $trades[] = $trade;

                $this->logger->info(json_encode($trade));
                $divergences[] = $result->getExtraData()['divergence_line'];
            }
            if($result->isShort()  && $openTradePrice > 0) {
                $percentage = ($currentCandle->getClosePrice()/$openTradePrice) - 1;
                $totalPercentage += $percentage;
                $initialInvestment *= ($percentage + 1);

                $trade = $this->newTestTrade($currentCandle, TradeTypes::TRADE_SELL);
                $trade["percentage"] = round($percentage * 100, 2);
                $trades[] = $trade;

                $this->logger->info(json_encode($trade));

                $openTradePrice = 0;
                $divergences[] = $result->getExtraData()['divergence_line'];
            }
        }

        $percentage = (($initialInvestment / 1000) - 1) * 100;

        if(isset($currentCandle)) {
            $this->saveAlgoTestResult($algo, $percentage, count($trades), $initialFrom, $currentCandle->getCloseTime());
        }

        $trade = "percentage $percentage";
        //$trades[] = $trade;
        $this->logger->info($trade);

        return $divergences;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @param bool $logAllTrades
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
        $totalPercentage = 0;

        $initialInvestment = 1000;

        $trades = [];
        for($i=$lastPositionCandles; $i < count($candles); $i++) {
            $auxData = array_slice($candles, $i - $lastPositionCandles, $candlesToLoad);
            /** TODO delete candles from array after using them
             * TODO for some reason it doesn´t calculate indicators properly
             */
            //array_shift($candles);

            $currentCandle = $auxData[count($auxData) - 1];

            $this->strategies->setData($auxData);
            $result = $this->strategies->runStrategy($algo);

            if($openTradePrice > 0) {
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

            if(($result->isLong()) && $openTradePrice == 0) {
                $openTradePrice = $currentCandle->getClosePrice();

                $trade = $this->newTestTrade($currentCandle, TradeTypes::TRADE_BUY);
                $trades[] = $trade;

                $this->logger->info(json_encode($trade));
            }
            if(($result->isShort() || $short) && $openTradePrice > 0) {
                $percentage = ($currentCandle->getClosePrice()/$openTradePrice) - 1;
                $totalPercentage += $percentage;
                $initialInvestment *= ($percentage + 1);

                $trade = $this->newTestTrade($currentCandle, TradeTypes::TRADE_SELL);
                $trade["percentage"] = round($percentage * 100, 2);
                $trade["stopLoss_takeProfit"] = $short;
                $trades[] = $trade;

                $this->logger->info(json_encode($trade));

                $openTradePrice = 0;
            }
        }

        $percentage = (($initialInvestment / 1000) - 1) * 100;

        if(isset($currentCandle)) {
            $this->saveAlgoTestResult($algo, $percentage, count($trades), $initialFrom, $currentCandle->getCloseTime());
        }

        $trade = "percentage $percentage";
        //$trades[] = $trade;
        $this->logger->info($trade);

        return $trades;
    }

    /**
     * @param BotAlgorithm $algo
     * @param Candle[] $candles
     * @return StrategyResult|bool
     */
    public function runAlgo(BotAlgorithm $algo, $candles)
    {
        $this->strategies->setData($candles);
        return $this->strategies->runStrategy($algo);
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

        $this->entityManager->persist($testResult);
        $this->entityManager->flush();
    }
}