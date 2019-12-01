<?php


namespace App\Entity;


class DivergenceLine
{
    /**
     * @var IndicatorPoint
     */
    private $firstPoint;

    /**
     * @var IndicatorPoint
     */
    private $secondPoint;

    /**
     * @var float
     */
    private $differencePerPeriod;

    /**
     * @var int
     */
    private $length;

    /**
     * DivergenceLine constructor.
     * @param IndicatorPoint $firstPoint
     * @param IndicatorPoint $secondPoint
     */
    public function __construct(IndicatorPoint $firstPoint, IndicatorPoint $secondPoint)
    {
        $this->firstPoint = $firstPoint;
        $this->secondPoint = $secondPoint;
    }

    /**
     * @return float
     */
    public function getDifferencePerPeriod(): float
    {
        if(is_null($this->differencePerPeriod)) {
            $this->differencePerPeriod = ($this->firstPoint->getValue() - $this->secondPoint->getValue()) /
                ($this->secondPoint->getPeriod());
        }
        return $this->differencePerPeriod;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        if(is_null($this->length)) {
            $this->length = $this->secondPoint->getPeriod() - $this->firstPoint->getPeriod();
        }
        return $this->length;
    }

}