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

    /**
     * @param array $data
     * @return StrategyResult
     */
    public function macd(array $data)
    {
        $result = new StrategyResult();
        $macd = $this->indicators->macd($data);

        if($macd > 0) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param array $data
     * @return StrategyResult
     */
    public function emaScalp(array $data) : StrategyResult
    {
        $result = new StrategyResult();
        $red = $redp = $green = $greenp = [];

        $e1 = [2,3,4,5,6,7,8,9,10,11,12,13,14,15];      // red
        $e3 = [44,47,50,53,56,59,62,65,68,71,74];       // green
        foreach ($e1 as $e) {
            $red[] = $this->indicators->ema($data['close'], $e);
            $redp[] = $this->indicators->ema($data['close'], $e, 1); // prior
        }
        $red_avg = (array_sum($red)/count($red));
        $redp_avg = (array_sum($redp)/count($redp));


        foreach ($e3 as $e) {
            $green[] = $this->indicators->ema($data['close'], $e);
        }
        $green_avg = (array_sum($green)/count($green));

        if ($red_avg < $green_avg && $redp_avg > $green_avg){
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        }
        if ($red_avg > $green_avg && $redp_avg < $green_avg){
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param array $data
     * @param int $period1
     * @param int $period2
     * @return StrategyResult
     */
    public function emaCrossover(array $data, $period1 = 10, $period2 = 20) : StrategyResult
    {
        $period1EMA = $this->indicators->ema($data['close'], $period1);
        $period1PriorEMA = $this->indicators->ema($data['close'], $period1, 1); // prior

        $period2EMA = $this->indicators->ema($data['close'], $period2);
        $period2PriorEMA = $this->indicators->ema($data['close'], $period2, 1); // prior

        //error_log("period 1 $period1EMA period 1 prior $period1PriorEMA period 2 $period2EMA period 2 prior $period2PriorEMA");

        return $this->checkCrossMovingAverages($period1EMA, $period1PriorEMA, $period2EMA, $period2PriorEMA);
    }

    /**
     * @param array $data
     * @param int $period1
     * @param int $period2
     * @return StrategyResult
     */
    public function maCrossover(array $data, $period1 = 10, $period2 = 50) : StrategyResult
    {
        $period1MA = $this->indicators->ma($data['close'], $period1);
        $period1PriorMA = $this->indicators->ma($data['close'], $period1, 1); // prior

        $period2MA = $this->indicators->ma($data['close'], $period2);
        $period2PriorMA = $this->indicators->ma($data['close'], $period2, 1); // prior

        return $this->checkCrossMovingAverages($period1MA, $period1PriorMA, $period2MA, $period2PriorMA);
    }

    /**
     * @param array $data
     * @return StrategyResult
     */
    public function guppyCrossover(array $data) : StrategyResult
    {
        $result = new StrategyResult();

        $shortPeriods = [3,5,8,10,12,15];
        $longPeriods = [30,35,40,45,50,60];

        $currentShortEMAs = $priorShortEMAs = $currentLongEMAs = $priorLongEMAs = [];

        foreach ($shortPeriods as $shortPeriod) {
            $currentShortEMAs[] = $this->indicators->ema($data['close'], $shortPeriod);
            $priorShortEMAs[] = $this->indicators->ema($data['close'], $shortPeriod, 1);
        }

        foreach ($longPeriods as $longPeriod) {
            $currentLongEMAs[] = $this->indicators->ema($data['close'], $longPeriod);
            $priorLongEMAs[] = $this->indicators->ema($data['close'], $longPeriod, 1);
        }

        $minShortEMA = min($currentShortEMAs);
        $maxShortEMA = max($currentShortEMAs);

        $minPriorShortEMA = min($priorShortEMAs);
        $maxPriorShortEMA = max($priorShortEMAs);

        $minLongEMA = min($currentLongEMAs);
        $maxLongEMA = max($currentLongEMAs);

        $minPriorLongEMA = min($priorLongEMAs);
        $maxPriorLongEMA = max($priorLongEMAs);

        if($minShortEMA >= $maxLongEMA) {
            if($minPriorShortEMA < $maxPriorLongEMA) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            }
        } else if($minLongEMA >= $maxShortEMA) {
            if($minPriorLongEMA < $maxPriorShortEMA) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }

    /**
     * @param $period1
     * @param $period1Prior
     * @param $period2
     * @param $period2Prior
     * @return StrategyResult
     */
    private function checkCrossMovingAverages($period1, $period1Prior, $period2, $period2Prior) : StrategyResult
    {
        $result = new StrategyResult();

        if($period1 > $period2 && $period1Prior <= $period2Prior){
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        }
        if($period1 < $period2 && $period1Prior >= $period2Prior){
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }
}