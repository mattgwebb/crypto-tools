<?php


namespace App\Service;

use App\Entity\Candle;
use App\Entity\CurrencyPair;
use App\Entity\TimeFrames;
use App\Entity\Trade;


class KrakenAPI extends ApiInterface
{

    protected $timeFrames = [
        TimeFrames::TIMEFRAME_5M => '5',
        TimeFrames::TIMEFRAME_15M => '15',
        TimeFrames::TIMEFRAME_30M => '30',
        TimeFrames::TIMEFRAME_45M => '45',
        TimeFrames::TIMEFRAME_1H => '60',
        TimeFrames::TIMEFRAME_2H => '120',
        TimeFrames::TIMEFRAME_3H => '180',
        TimeFrames::TIMEFRAME_4H => '240',
        TimeFrames::TIMEFRAME_1D > '1440',
        TimeFrames::TIMEFRAME_1W => '10080'
    ];

    /**
     * @param CurrencyPair $currencyPair
     * @param $timeFrame
     * @param $startTime
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function getCandlesData(CurrencyPair $currencyPair, $timeFrame, $startTime) : array
    {
        try {
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute()."OHLC",
                ['query' => [
                        'interval' => $timeFrame,
                        'pair' => $currencyPair->getSymbol(),
                        'since' => $startTime
                    ]
                ]);
            $response = $response->toArray();
            $rawCandles = reset($response['result']);
        } catch (\Exception $e) {
            throw $e;
        }

        return $rawCandles;
    }

    /**
     * @return string
     */
    protected function getAPIBaseRoute() : string
    {
        return "https://api.kraken.com/0/public/";
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param $rawData
     * @return Candle
     */
    protected function getCandleFromRawData(CurrencyPair $currencyPair, $rawData): Candle
    {
        $candle = new Candle();
        $candle->setOpenTime($rawData[0]);
        $candle->setOpenPrice($rawData[1]);
        $candle->setHighPrice($rawData[2]);
        $candle->setLowPrice($rawData[3]);
        $candle->setClosePrice($rawData[4]);
        $candle->setVolume($rawData[6]);
        $candle->setCloseTime($rawData[0] + 14399);
        $candle->setCurrencyPair($currencyPair);
        return $candle;
    }

    public function getUserBalance(): array
    {
        // TODO: Implement getUserBalance() method.
        return [];
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $side
     * @param float $quantity
     * @return Trade
     */
    public function marketTrade(CurrencyPair $currencyPair, int $side, float $quantity): Trade
    {
        // TODO: Implement marketTrade() method.
        return new Trade();
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param float $quantity
     * @param float $price
     * @return Trade
     */
    public function stopLossTrade(CurrencyPair $currencyPair, float $quantity, float $price): Trade
    {
        // TODO: Implement stopLossTrade() method.
        return new Trade();
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param float $quantity
     * @param float $price
     * @param float $stopPrice
     * @return Trade
     */
    public function stopLossLimitTrade(CurrencyPair $currencyPair, float $quantity, float $price, float $stopPrice): Trade
    {
        // TODO: Implement stopLossLimitTrade() method.
        return new Trade();
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $limit
     * @return array
     */
    public function getOrderBook(CurrencyPair $currencyPair, int $limit = 100): array
    {
        // TODO: Implement getOrderBook() method.
        return [];
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return float
     */
    public function getOpenInterest(CurrencyPair $currencyPair): float
    {
        // TODO: Implement getOpenInterest() method.
        return 0.0;
    }
}