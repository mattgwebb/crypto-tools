<?php


namespace App\Entity\Algorithm;


class StrategyCombination
{
    /**
     * @var string
     */
    private $operator;

    /**
     * @var StrategyConfig[]
     */
    private $strategyConfigList;

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * @return StrategyConfig[]
     */
    public function getStrategyConfigList(): array
    {
        return $this->strategyConfigList;
    }

    /**
     * @param StrategyConfig[] $strategyConfigList
     */
    public function setStrategyConfigList(array $strategyConfigList): void
    {
        $this->strategyConfigList = $strategyConfigList;
    }

    /**
     * @param StrategyConfig $strategyConfig
     */
    public function addStrategyConfig(StrategyConfig $strategyConfig): void
    {
        $this->strategyConfigList[] = $strategyConfig;
    }
}