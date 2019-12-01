<?php


namespace App\Entity;


class IndicatorPoint
{
    /**
     * @var int
     */
    private $period;

    /**
     * @var float
     */
    private $value;

    /**
     * IndicatorPoint constructor.
     * @param int $period
     * @param float $value
     */
    public function __construct(int $period, float $value)
    {
        $this->period = $period;
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

}