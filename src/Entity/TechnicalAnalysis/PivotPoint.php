<?php


namespace App\Entity\TechnicalAnalysis;


use App\Entity\Data\Candle;

class PivotPoint
{
    /**
     * @var Candle
     */
    private $candle;

    /**
     * @var int
     */
    private $type;

    /**
     * PivotPoint constructor.
     * @param Candle $candle
     * @param int $type
     */
    public function __construct(Candle $candle, int $type)
    {
        $this->candle = $candle;
        $this->type = $type;
    }

    /**
     * @return Candle
     */
    public function getCandle(): Candle
    {
        return $this->candle;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }
}