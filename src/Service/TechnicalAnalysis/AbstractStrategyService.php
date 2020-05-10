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
}