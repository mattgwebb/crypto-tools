<?php


namespace App\Entity\TechnicalAnalysis;


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
     * @var float
     */
    private $percentageChange;

    /**
     * @var float
     */
    private $percentageDivergenceWithPrice;

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $type = DivergenceTypes::NO_DIVERGENCE;

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

    /**
     * @return IndicatorPoint
     */
    public function getFirstPoint(): IndicatorPoint
    {
        return $this->firstPoint;
    }

    /**
     * @return IndicatorPoint
     */
    public function getSecondPoint(): IndicatorPoint
    {
        return $this->secondPoint;
    }

    /**
     * @return float
     */
    public function getPercentageDivergenceWithPrice(): float
    {
        return $this->percentageDivergenceWithPrice;
    }

    /**
     * @param float $percentageDivergenceWithPrice
     */
    public function setPercentageDivergenceWithPrice(float $percentageDivergenceWithPrice): void
    {
        $this->percentageDivergenceWithPrice = $percentageDivergenceWithPrice;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function hasBullishDivergence(): bool {
        return in_array($this->getType(),
            [DivergenceTypes::BULLISH_REGULAR_DIVERGENCE, DivergenceTypes::BULLISH_HIDDEN_DIVERGENCE]);
    }

    /**
     * @return bool
     */
    public function hasBearishDivergence(): bool {
        return in_array($this->getType(),
            [DivergenceTypes::BEARISH_REGULAR_DIVERGENCE, DivergenceTypes::BEARISH_HIDDEN_DIVERGENCE]);
    }
}