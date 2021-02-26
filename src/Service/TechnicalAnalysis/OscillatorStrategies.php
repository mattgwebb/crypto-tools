<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\StrategyResult;

class OscillatorStrategies extends AbstractStrategyService
{
    /**
     * @param array $data
     * @param float $rsiSell
     * @param float $rsiBuy
     * @param int $period
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function rsi(array $data, float $rsiBuy = 30.00, float $rsiSell = 70.00, int $period = 14, bool $crossOnly = false) : StrategyResult
    {
        $result = new StrategyResult();
        $rsi = $this->indicators->rsi($data, $period);

        if($crossOnly) {
            $rsiPrior = $this->indicators->rsi($data, $period, true);

            if($rsi >= $rsiBuy && $rsiPrior < $rsiBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($rsi <= $rsiSell && $rsiPrior > $rsiSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        } else {
            if($rsi < $rsiBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($rsi > $rsiSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }

    /**
     * @param array $data
     * @param int $stochSell
     * @param int $stochBuy
     * @param int $period
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function stoch(array $data, int $stochSell = 80, int $stochBuy = 20, int $period = 14, bool $crossOnly = false) : StrategyResult
    {
        $result = new StrategyResult();
        $stoch = $this->indicators->stoch($data, $period, 3);

        if($crossOnly) {
            $stochPrior = $this->indicators->stoch($data, $period, 3,true);

            if($stoch >= $stochBuy && $stochPrior < $stochBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($stoch <= $stochSell && $stochPrior > $stochSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        } else {
            if($stoch < $stochBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($stoch > $stochSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }

    /**
     * @param array $data
     * @param int $mfiSell
     * @param int $mfiBuy
     * @param int $period
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function mfi(array $data, int $mfiSell = 80, int $mfiBuy = 20, int $period = 14, bool $crossOnly = false) : StrategyResult
    {
        $result = new StrategyResult();
        $mfi = $this->indicators->mfi($data, $period);

        if($crossOnly) {
            $mfiPrior = $this->indicators->mfi($data, $period, true);

            if($mfi >= $mfiBuy && $mfiPrior < $mfiBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($mfi <= $mfiSell && $mfiPrior > $mfiSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        } else {
            if($mfi < $mfiBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($mfi > $mfiSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }
}