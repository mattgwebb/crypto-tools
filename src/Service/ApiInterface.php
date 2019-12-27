<?php


namespace App\Service;


use App\Entity\Candle;
use App\Entity\CurrencyPair;
use App\Entity\TimeFrames;
use App\Entity\Trade;
use Doctrine\Common\Collections\ArrayCollection;


abstract class ApiInterface extends ThirdPartyAPI
{
    protected $timeFrames = [
        TimeFrames::TIMEFRAME_5M => '5m',
        TimeFrames::TIMEFRAME_15M => '15m',
        TimeFrames::TIMEFRAME_30M => '30m',
        TimeFrames::TIMEFRAME_45M => '45m',
        TimeFrames::TIMEFRAME_1H => '1h',
        TimeFrames::TIMEFRAME_2H => '2h',
        TimeFrames::TIMEFRAME_3H => '3h',
        TimeFrames::TIMEFRAME_4H => '4h',
        TimeFrames::TIMEFRAME_1D > '1d',
        TimeFrames::TIMEFRAME_1W => '1w'
    ];

    /**
     * @param CurrencyPair $currencyPair
     * @param $timeFrame
     * @param $startTime
     * @return ArrayCollection
     */
    public function getCandles(CurrencyPair $currencyPair, $timeFrame, $startTime) : ArrayCollection
    {
        $specificTimeFrame = $this->getCorrectTimeFrame($timeFrame);
        $rawData = $this->getCandlesData($currencyPair, $specificTimeFrame, $startTime);
        $candles = new ArrayCollection();

        foreach($rawData as $rawCandle) {
            $candle = $this->getCandleFromRawData($currencyPair, $rawCandle);
            $candles->add($candle);
        }

        return $candles;
    }

    /**
     * @return string
     */
    protected abstract function getAPIBaseRoute() : string;

    /**
     * @param CurrencyPair $currencyPair
     * @param $timeFrame
     * @param $startTime
     * @return ArrayCollection
     */
    protected abstract function getCandlesData(CurrencyPair $currencyPair, $timeFrame, $startTime) : array;

    /**
     * @param CurrencyPair $currencyPair
     * @param $rawData
     * @return Candle
     */
    protected abstract function getCandleFromRawData(CurrencyPair $currencyPair, $rawData) : Candle;

    /**
     * @return array
     */
    public abstract function getUserBalance() : array;

    /**
     * @param CurrencyPair $currencyPair
     * @param int $side
     * @param float $quantity
     * @return Trade
     */
    public abstract function marketTrade(CurrencyPair $currencyPair, int $side, float $quantity) : Trade;

    /**
     * @param CurrencyPair $currencyPair
     * @param float $quantity
     * @param float $price
     * @return Trade
     */
    public abstract function stopLossTrade(CurrencyPair $currencyPair, float $quantity, float $price) : Trade;

    /**
     * @param CurrencyPair $currencyPair
     * @param float $quantity
     * @param float $price
     * @param float $stopPrice
     * @return Trade
     */
    public abstract function stopLossLimitTrade(CurrencyPair $currencyPair, float $quantity, float $price, float $stopPrice) : Trade;

    /**
     * @param CurrencyPair $currencyPair
     * @param int $limit
     * @return array
     */
    public abstract function getOrderBook(CurrencyPair $currencyPair, int $limit = 100) : array;

    /**
     * @param CurrencyPair $currencyPair
     * @return float
     */
    public abstract function getOpenInterest(CurrencyPair $currencyPair) : float;

    /**
     * @param $timeFrame
     * @return string
     */
    private function getCorrectTimeFrame($timeFrame) : string
    {
        return $this->timeFrames[$timeFrame];
    }
}