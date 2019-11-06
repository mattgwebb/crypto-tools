<?php


namespace App\Model;


use App\Entity\BotAlgorithm;
use App\Entity\StrategyResult;
use App\Repository\BotAlgorithmRepository;
use App\Repository\CurrencyPairRepository;
use App\Service\Strategies;
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
     * BotAlgorithmManager constructor.
     * @param BotAlgorithmRepository $botAlgorithmRepo
     * @param CurrencyPairRepository $currencyRepo
     * @param Strategies $strategies
     * @param LoggerInterface $algosLogger
     */
    public function __construct(BotAlgorithmRepository $botAlgorithmRepo, CurrencyPairRepository $currencyRepo, Strategies $strategies, LoggerInterface $algosLogger)
    {
        $this->botAlgorithmRepo = $botAlgorithmRepo;
        $this->currencyPairRepo = $currencyRepo;
        $this->strategies = $strategies;
        $this->logger = $algosLogger;
    }

    /**
     * @param BotAlgorithm $algo
     * @return array
     * @throws \Exception
     */
    public function runTest(BotAlgorithm $algo)
    {
        $this->logger->info("********************* New test  ************************");
        $this->logger->info(json_encode($algo));
        $candles = $this->currencyPairRepo->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame());

        $openTradePrice = 0;
        $totalPercentage = 0;

        $initialInvestment = 1000;

        $trades = [];
        for($i=50; $i < count($candles); $i++) {
            $auxData = array_slice($candles, 0, $i);

            $currentCandle = $auxData[count($auxData) - 1];

            $this->strategies->setData($auxData);
            $result = $this->strategies->runStrategy($algo->getStrategy());

            if($openTradePrice > 0) {
                if($algo->getStopLoss()) {
                    $stopLoss = $this->strategies->stopLosses($openTradePrice, $algo->getStopLoss())->getTradeResult() == StrategyResult::TRADE_SHORT;
                } else {
                    $stopLoss = false;
                }
                if($algo->getTakeProfit()) {
                    $takeProfit = $this->strategies->takeProfit($openTradePrice, $algo->getTakeProfit())->getTradeResult() == StrategyResult::TRADE_SHORT;
                } else {
                    $takeProfit = false;
                }

                $short = $stopLoss || $takeProfit;
            } else {
                $short = false;
            }

            if(($result->getTradeResult() == StrategyResult::TRADE_LONG)
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
            if(($result->getTradeResult() == StrategyResult::TRADE_SHORT || $short) && $openTradePrice > 0) {
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
}