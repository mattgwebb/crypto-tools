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
     * Open time
     * @var int
     */
    private $timestamp;

    /**
     * @var float
     */
    private $price;

    /**
     * IndicatorPoint constructor.
     * @param int $period
     * @param float $value
     * @param float $price
     * @param int $timestamp
     */
    public function __construct(int $period, float $value, float $price, int $timestamp)
    {
        $this->period = $period;
        $this->value = $value;
        $this->price = $price;
        $this->timestamp = $timestamp;
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

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }
}