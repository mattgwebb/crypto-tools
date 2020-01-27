<?php


namespace App\Model;


use App\Entity\BotAlgorithm;
use App\Entity\Candle;
use App\Entity\StrategyResult;
use App\Repository\BotAlgorithmRepository;
use App\Repository\CurrencyPairRepository;
use App\Repository\TradeRepository;
use App\Service\Strategies;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;

class BotAlgorithmManager
{
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
     * BotAlgorithmManager constructor.
     * @param BotAlgorithmRepository $botAlgorithmRepo
     * @param CurrencyPairRepository $currencyRepo
     * @param Strategies $strategies
     * @param LoggerInterface $algosLogger
     * @param TradeRepository $tradeRepository
     */
    public function __construct(BotAlgorithmRepository $botAlgorithmRepo, CurrencyPairRepository $currencyRepo,
                                Strategies $strategies, LoggerInterface $algosLogger, TradeRepository $tradeRepository)
    {
        $this->botAlgorithmRepo = $botAlgorithmRepo;
        $this->currencyPairRepo = $currencyRepo;
        $this->strategies = $strategies;
        $this->logger = $algosLogger;
        $this->tradeRepository = $tradeRepository;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @return array
     * @throws \Exception
     */
    public function runDivergenceTest(BotAlgorithm $algo, int $from = 0, int $to = 0)
    {
        $this->logger->info("********************* New test  ************************");
        $this->logger->info(json_encode($algo));

        $from -= 99 * ($algo->getTimeFrame() * 60);
        $candles = $this->currencyPairRepo->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame(), $from, $to);

        $divergences = [];

        for($i=99; $i < count($candles); $i++) {
            $auxData = array_slice($candles, $i - 99, 100);
            /** TODO delete candles from array after using them
             * TODO for some reason it doesn´t calculate indicators properly
             */
            //array_shift($candles);

            $currentCandle = $auxData[count($auxData) - 1];

            $this->strategies->setData($auxData);
            $result = $this->strategies->runStrategy($algo->getStrategy());

            if(!$result->noTrade()) {
                $divergences[] = $result->getExtraData()['divergence_line'];
            }
        }

        return $divergences;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @return array
     * @throws \Exception
     */
    public function runTest(BotAlgorithm $algo, int $from = 0, int $to = 0)
    {
        $this->logger->info("********************* New test  ************************");
        $this->logger->info(json_encode($algo));

        $from -= 99 * ($algo->getTimeFrame() * 60);
        $candles = $this->currencyPairRepo->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame(), $from, $to);

        $openTradePrice = 0;
        $totalPercentage = 0;

        $initialInvestment = 1000;

        $trades = [];
        for($i=99; $i < count($candles); $i++) {
            $auxData = array_slice($candles, $i - 99, 100);
            /** TODO delete candles from array after using them
             * TODO for some reason it doesn´t calculate indicators properly
             */
            //array_shift($candles);

            $currentCandle = $auxData[count($auxData) - 1];

            $this->strategies->setData($auxData);
            $result = $this->strategies->runStrategy($algo->getStrategy());

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

            if(($result->isLong())
                && $openTradePrice == 0) {
                $openTradePrice = $currentCandle->getClosePrice();
                $date = new \DateTime('@' .$currentCandle->getCloseTime());
                $date->setTimezone(new \DateTimeZone("Europe/Madrid"));
                $trade = ["trade" => "long",
                    "time" => date_format($date, 'D M j G:i:s'),
                    "timestamp"=> $currentCandle->getCloseTime() * 1000,
                    "price" => $openTradePrice];
                $trades[] = $trade;

                $this->logger->info(json_encode($trade));

            }
            if(($result->isShort() || $short) && $openTradePrice > 0) {
                $percentage = ($currentCandle->getClosePrice()/$openTradePrice) - 1;
                $totalPercentage += $percentage;
                $initialInvestment *= ($percentage + 1);
                $date = new \DateTime('@' .$currentCandle->getCloseTime());
                $date->setTimezone(new \DateTimeZone("Europe/Madrid"));
                $trade = ["trade" => "short",
                    "time" => date_format($date, 'D M j G:i:s'),
                    "timestamp"=> $currentCandle->getCloseTime() * 1000,
                    "price" => $currentCandle->getClosePrice(),
                    "percentage" => round($percentage * 100, 2),
                    "stopLoss_takeProfit" => $short];
                $trades[] = $trade;

                $this->logger->info(json_encode($trade));
                $openTradePrice = 0;
            }


        }

        $percentage = (($initialInvestment / 1000) - 1) * 100;
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
        return $this->strategies->runStrategy($algo->getStrategy());
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
}