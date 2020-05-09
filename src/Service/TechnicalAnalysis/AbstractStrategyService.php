<?php


namespace App\Service\TechnicalAnalysis;


abstract class AbstractStrategyService
{
    /**
     * @var Indicators
     */
    protected $indicators;

    /**
     * DivergenceStrategies constructor.
     * @param Indicators $indicators
     */
    public function __construct(Indicators $indicators)
    {
        $this->indicators = $indicators;
    }

    /**
     * @param array $data
     * @return float
     */
    protected function getCurrentPrice(array $data)
    {
        $count = count($data['close']);
        return (float)$data['close'][$count - 1];
    }

    /**
     * @param array $data
     * @return float
     */
    protected function getPreviousPrice(array $data)
    {
        $count = count($data['close']);
        return (float)$data['close'][$count - 2];
    }
}