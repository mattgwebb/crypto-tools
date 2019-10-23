<?php


namespace App\Model;


use App\Entity\BotAlgorithm;
use App\Entity\StrategyResult;
use App\Repository\BotAlgorithmRepository;
use App\Repository\CurrencyPairRepository;
use App\Service\Strategies;

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
     * BotAlgorithmManager constructor.
     * @param BotAlgorithmRepository $botAlgorithmRepo
     * @param CurrencyPairRepository $currencyRepo
     * @param Strategies $strategies
     */
    public function __construct(BotAlgorithmRepository $botAlgorithmRepo, CurrencyPairRepository $currencyRepo, Strategies $strategies)
    {
        $this->botAlgorithmRepo = $botAlgorithmRepo;
        $this->currencyPairRepo = $currencyRepo;
        $this->strategies = $strategies;
    }

    /**
     * @param BotAlgorithm $algo
     * @return array
     * @throws \Exception
     */
    public function runTest(BotAlgorithm $algo)
    {
        $candles = $this->currencyPairRepo->getCandlesByTimeFrame($algo->getCurrencyPair()->getFirstCurrency(), $algo->getTimeFrame(), 1560700800);

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
                $trades[] = json_encode(["trade" => "long", "time" => date_format($date, 'D M j G:i:s'), "price" => $openTradePrice]);

            }
            if(($result->getTradeResult() == StrategyResult::TRADE_SHORT || $short) && $openTradePrice > 0) {
                $percentage = ($currentCandle->getClosePrice()/$openTradePrice) - 1;
                $totalPercentage += $percentage;
                $initialInvestment *= ($percentage + 1);
                $date = new \DateTime('@' .$currentCandle->getCloseTime());
                $date->setTimezone(new \DateTimeZone("Europe/Madrid"));
                $trades[] = json_encode(["trade" => "short",
                    "time" => date_format($date, 'D M j G:i:s'),
                    "price" => $currentCandle->getClosePrice(),
                    "percentage" => $percentage,
                    "stopLoss/takeProfit" => $short]);

                $openTradePrice = 0;
            }


        }

        $trades[] = "percentage $totalPercentage investment $initialInvestment";

        return $trades;
    }
}