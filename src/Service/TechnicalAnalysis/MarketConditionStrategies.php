<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Data\CurrencyPair;
use App\Entity\Data\TimeFrames;
use App\Entity\TechnicalAnalysis\IndicatorValue;
use App\Repository\TechnicalAnalysis\IndicatorValueRepository;

class MarketConditionStrategies extends AbstractStrategyService
{

    /**
     * @var IndicatorValueRepository
     */
    private $indicatorValueRepository;

    /**
     * @var array
     */
    private $indicatorCachedValues = [];

    /**
     * MarketConditionStrategies constructor.
     * @param Indicators $indicators
     * @param IndicatorValueRepository $indicatorValueRepository
     */
    public function __construct(Indicators $indicators, IndicatorValueRepository $indicatorValueRepository)
    {
        parent::__construct($indicators);
        $this->indicatorValueRepository = $indicatorValueRepository;
    }


    /**
     * @param array $data
     * @param string $operator
     * @param float $value
     * @param int $period
     * @return bool
     */
    public function adxValue(array $data, string $operator = '>=', float $value = 20, int $period = 14) : bool
    {
        $adx = $this->indicators->adx($data, $period);
        return $this->compareValues($adx, $value, $operator);
    }

    /**
     * @param array $data
     * @param string $operator
     * @param float $value
     * @param int $period
     * @return bool
     */
    public function rsiValue(array $data, string $operator = '<', float $value = 75, int $period = 14) : bool
    {
        $rsi = $this->indicators->rsi($data, $period);
        return $this->compareValues($rsi, $value, $operator);
    }

    /**
     * @param array $data
     * @param float $percentageRange
     * @param int $period
     * @return bool
     */
    public function notRangingPeriod(array $data, float $percentageRange = 5.00, int $period = 100)
    {
        $lastClose = array_pop($data['close']);

        list($rangeLow, $rangeHigh) = $this->indicators->priceRangePeriod($data, $period);

        $existingRange = ($rangeHigh / $rangeLow) <= (1 + ($percentageRange/100));

        return !$existingRange || $lastClose > $rangeHigh;
    }

    /**
     * @param array $data
     * @param int $period
     * @param float $dev
     * @return bool
     */
    public function notInKeltnerChannel(array $data, int $period = 20, float $dev = 2)
    {
        $keltnerChannelPeriod = $this->indicators->keltnerChannelPeriod($data, $period, $dev, $dev);
        $bollingerBandsPeriod = $this->indicators->bollingerBandsPeriod($data, $period);

        $lastKey = array_key_last($keltnerChannelPeriod[0]);

        $bollingerUpperBand = $bollingerBandsPeriod[0][$lastKey];
        $bollingerLowerBand = $bollingerBandsPeriod[2][$lastKey];

        $keltnerUpperBand = $keltnerChannelPeriod[0][$lastKey];
        $keltnerLowerBand = $keltnerChannelPeriod[2][$lastKey];

        return $bollingerUpperBand >= $keltnerUpperBand || $bollingerLowerBand <= $keltnerLowerBand;
    }

    /**
     * @param array $candles
     * @param string $indicator
     * @param string $operator
     * @param float $value
     * @param int $timeFrame
     * @return bool
     */
    public function savedValue(array $candles, string $indicator, string $operator, float $value, int $timeFrame = 1440)
    {
        $currentCandle = $candles[count($candles) - 1];
        $pair = $currentCandle->getCurrencyPair();

        $indicatorValue = $this->getSavedIndicatorValue($pair, $indicator, $currentCandle->getCloseTime(), $timeFrame);

        if($indicatorValue === false) {
            return false;
        }
        return $this->compareValues($indicatorValue, $value, $operator);
    }

    /**
     * @param CurrencyPair $pair
     * @param string $indicator
     * @param int $lastClose
     * @param int $timeFrame
     * @return float
     */
    private function getSavedIndicatorValue(CurrencyPair $pair, string $indicator, int $lastClose, int $timeFrame = TimeFrames::TIMEFRAME_1D)
    {
        $lastDailyClose = $this->getLastClose($lastClose, $timeFrame);

        if(!isset($this->indicatorCachedValues[$indicator][$lastDailyClose])) {

            /** @var IndicatorValue $value */
            $value = $this->indicatorValueRepository->findOneBy([
                    'currencyPair' => $pair,
                    'createdAt' => $lastDailyClose,
                    'indicator' => $indicator
            ]);

            $this->indicatorCachedValues[$indicator][$lastDailyClose] = $value ? $value->getValue() : false;
        }
        return $this->indicatorCachedValues[$indicator][$lastDailyClose];
    }

    /**
     * @param $firstValue
     * @param $secondValue
     * @param string $operator
     * @return bool
     */
    private function compareValues($firstValue, $secondValue, string $operator)
    {
        if($operator == '>') {
            return $firstValue > $secondValue;
        }

        if($operator == '>=') {
            return $firstValue >= $secondValue;
        }

        if($operator == '<') {
            return $firstValue < $secondValue;
        }

        if($operator == '<=') {
            return $firstValue <= $secondValue;
        }

        if($operator == '==') {
            return $firstValue == $secondValue;
        }

        return false;
    }

    /**
     * @param int $timestamp
     * @param int $timeFrame
     * @return int
     */
    private function getLastClose(int $timestamp, int $timeFrame)
    {
        $timeFrameSeconds = $timeFrame * 60;

        $difference = ($timestamp + 1) % $timeFrameSeconds;

        return $difference == 0 ? $timestamp : $timestamp - $difference;
    }
}