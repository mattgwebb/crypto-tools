<?php


namespace App\Service;

use App\Entity\Candle;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\Currency;


class BinanceAPI extends ApiInterface
{

    /**
     * @param Currency $currency
     * @param $timeFrame
     * @param $startTime
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function getCandlesData(Currency $currency, $timeFrame, $startTime) : array
    {
        $startTime = $startTime * 1000;
        $client = HttpClient::create();

        try {
            $response = $client->request('GET', $this->getAPIBaseRoute()."klines",
                ['query' => [
                        'interval' => $timeFrame,
                        'symbol' => $currency->getSymbol(),
                        'startTime' => $startTime
                    ]
                ]);
            $rawCandles = $response->toArray();
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
        return "https://api.binance.com/api/v1/";
    }

    /**
     * @param Currency $currency
     * @param $rawData
     * @return Candle
     */
    protected function getCandleFromRawData(Currency $currency, $rawData): Candle
    {
        $candle = new Candle();
        $candle->setOpenTime((int)($rawData[0]/1000));
        $candle->setOpenPrice($rawData[1]);
        $candle->setHighPrice($rawData[2]);
        $candle->setLowPrice($rawData[3]);
        $candle->setClosePrice($rawData[4]);
        $candle->setVolume($rawData[5]);
        $candle->setCloseTime((int)($rawData[6]/1000));
        $candle->setCurrency($currency);
        return $candle;
    }
}