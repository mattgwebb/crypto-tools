<?php


namespace App\Service\TechnicalAnalysis;


class MarketConditionStrategies extends AbstractStrategyService
{

    /**
     * @param array $data
     * @param string $operator
     * @param float $value
     * @param int $period
     * @return bool
     */
    public function adxValue(array $data, string $operator = '>=', float $value = 20, int $period = 14) : bool
    {
        $adx = $this->indicators->adx($data, $period);
        return $this->compareValues($adx, $value, $operator);
    }

    /**
     * @param array $data
     * @param string $operator
     * @param float $value
     * @param int $period
     * @return bool
     */
    public function rsiValue(array $data, string $operator = '<', float $value = 75, int $period = 14) : bool
    {
        $rsi = $this->indicators->rsi($data, $period);
        return $this->compareValues($rsi, $value, $operator);
    }

    /**
     * @param array $data
     * @param float $percentageRange
     * @param int $period
     * @return bool
     */
    public function notRangingPeriod(array $data, float $percentageRange = 5.00, int $period = 100)
    {
        $lastClose = array_pop($data['close']);

        list($rangeLow, $rangeHigh) = $this->indicators->priceRangePeriod($data, $period);

        $existingRange = ($rangeHigh / $rangeLow) <= (1 + ($percentageRange/100));

        return !$existingRange || $lastClose > $rangeHigh;
    }

    /**
     * @param $firstValue
     * @param $secondValue
     * @param string $operator
     * @return bool
     */
    private function compareValues($firstValue, $secondValue, string $operator)
    {
        if($operator == '>') {
            return $firstValue > $secondValue;
        }

        if($operator == '>=') {
            return $firstValue >= $secondValue;
        }

        if($operator == '<') {
            return $firstValue < $secondValue;
        }

        if($operator == '<=') {
            return $firstValue <= $secondValue;
        }

        if($operator == '==') {
            return $firstValue == $secondValue;
        }

        return false;
    }
}