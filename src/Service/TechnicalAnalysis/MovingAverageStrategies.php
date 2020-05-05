<?php


namespace App\Service\TechnicalAnalysis;



use App\Entity\Algorithm\StrategyResult;

class MovingAverageStrategies extends AbstractStrategyService
{
    /**
     * @param array $data
     * @param float $currentPrice
     * @param int $period
     * @return StrategyResult
     */
    public function ma(array $data, float $currentPrice, int $period = 20) : StrategyResult
    {
        $result = new StrategyResult();
        $ma = $this->indicators->ma($data['close'], $period);

        if($currentPrice > $ma) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($currentPrice < $ma) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param array $data
     * @param float $currentPrice
     * @param int $period
     * @return StrategyResult
     */
    public function ema(array $data, float $currentPrice ,int $period = 20) : StrategyResult
    {
        $result = new StrategyResult();
        $ema = $this->indicators->ema($data['close'], $period);

        if($currentPrice > $ema) {
            error_log("price {$currentPrice} ema $ema");
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($currentPrice < $ema) {
            error_log("price {$currentPrice} ema $ema");
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }
}