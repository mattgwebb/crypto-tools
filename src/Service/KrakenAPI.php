<?php


namespace App\Service;

use App\Entity\Candle;
use App\Entity\TimeFrames;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\Currency;


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
        $client = HttpClient::create();

        try {
            $response = $client->request('GET', $this->getAPIBaseRoute()."OHLC",
                ['query' => [
                        'interval' => $timeFrame,
                        'pair' => $currency->getSymbol(),
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
     * @param Currency $currency
     * @param $rawData
     * @return Candle
     */
    protected function getCandleFromRawData(Currency $currency, $rawData): Candle
    {
        $candle = new Candle();
        $candle->setOpenTime($rawData[0]);
        $candle->setOpenPrice($rawData[1]);
        $candle->setHighPrice($rawData[2]);
        $candle->setLowPrice($rawData[3]);
        $candle->setClosePrice($rawData[4]);
        $candle->setVolume($rawData[6]);
        $candle->setCloseTime($rawData[0] + 14399);
        $candle->setCurrency($currency);
        return $candle;
    }

    public function getUserBalance(): array
    {
        return [];
    }
}