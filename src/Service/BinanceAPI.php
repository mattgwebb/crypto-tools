<?php


namespace App\Service;

use App\Entity\Candle;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\Currency;


class BinanceAPI implements ApiInterface
{

    public function getCandles(Currency $currency, $timeFrame, $startTime)
    {
        $candles = new ArrayCollection();
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
            foreach($rawCandles as $rawCandle) {
                $candle = CandleFactory::createFromBinance($rawCandle, $currency);
                $candles->add($candle);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $candles;
    }

    public function getAPIBaseRoute() : string
    {
        return "https://api.binance.com/api/v1/";
    }
}