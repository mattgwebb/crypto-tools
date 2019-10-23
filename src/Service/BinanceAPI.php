<?php


namespace App\Service;

use App\Entity\Candle;
use App\Entity\CurrencyPair;
use Symfony\Component\HttpClient\HttpClient;

class BinanceAPI extends ApiInterface
{

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
        $startTime = $startTime * 1000;
        $client = HttpClient::create();

        try {
            $response = $client->request('GET', $this->getAPIBaseRoute()."klines",
                ['query' => [
                        'interval' => $timeFrame,
                        'symbol' => $currencyPair->getSymbol(),
                        'startTime' => $startTime,
                        'limit' => 1000
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
        return "https://api.binance.com/api/v3/";
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param $rawData
     * @return Candle
     */
    protected function getCandleFromRawData(CurrencyPair $currencyPair, $rawData): Candle
    {
        $candle = new Candle();
        $candle->setOpenTime((int)($rawData[0]/1000));
        $candle->setOpenPrice($rawData[1]);
        $candle->setHighPrice($rawData[2]);
        $candle->setLowPrice($rawData[3]);
        $candle->setClosePrice($rawData[4]);
        $candle->setVolume($rawData[5]);
        $candle->setCloseTime((int)($rawData[6]/1000));
        $candle->setCurrencyPair($currencyPair);
        return $candle;
    }

    /**
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getUserBalance(): array
    {
        $client = HttpClient::create();

        $secret = $_ENV['BINANCE_SECRET'];
        $key = $_ENV['BINANCE_KEY'];

        $timestamp = time() * 1000;

        $totalParams = "timestamp=$timestamp";
        $hash = hash_hmac ( "sha256", $totalParams, $secret);

        try {
            $response = $client->request('GET', $this->getAPIBaseRoute()."account",
                [
                    'query' => [
                        'timestamp' => $timestamp,
                        'signature' => $hash
                    ],
                    'headers' => [
                        'X-MBX-APIKEY' => $key,
                    ],
                ]);
            $data = $response->toArray();
        } catch (\Exception $e) {
            throw $e;
        }

        $balance = [];
        if(isset($data['balances'])) {
            foreach($data['balances'] as $currencyBalance) {
                $balance[$currencyBalance['asset']] = $currencyBalance['free'];
            }
        }
        return $balance;
    }
}