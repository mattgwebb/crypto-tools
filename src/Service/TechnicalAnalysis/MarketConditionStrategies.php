<?php


namespace App\Service\TechnicalAnalysis;


class MarketConditionStrategies extends AbstractStrategyService
{

    /**
     * @param array $data
     * @param int $period
     * @param float $value
     * @return bool
     */
    public function adxOver(array $data, int $period = 14, float $value = 20) : bool
    {
        $adx = $this->indicators->adx($data, $period);
        return $adx >= $value;
    }
}