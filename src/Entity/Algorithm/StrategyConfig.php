<?php


namespace App\Entity\Algorithm;


use App\Entity\TechnicalAnalysis\Strategy;

class StrategyConfig
{
    /**
     * @var Strategy
     */
    private $strategy;

    /**
     * @var array
     */
    private $configParams = [];

    /**
     * @return Strategy
     */
    public function getStrategy(): Strategy
    {
        return $this->strategy;
    }

    /**
     * @param Strategy $strategy
     */
    public function setStrategy(Strategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * @return array
     */
    public function getConfigParams(): array
    {
        return $this->configParams;
    }

    /**
     * @param array $configParams
     */
    public function setConfigParams(array $configParams): void
    {
        $this->configParams = $configParams;
    }
}