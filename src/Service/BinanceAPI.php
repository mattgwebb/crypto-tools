<?php


namespace App\Service;

use App\Entity\BookOrder;
use App\Entity\BookOrderTypes;
use App\Entity\Candle;
use App\Entity\CurrencyPair;
use App\Entity\Trade;
use App\Entity\TradeTypes;
use Symfony\Component\HttpClient\HttpClient;

class BinanceAPI extends ApiInterface
{

    private $tradeTypes = [
        TradeTypes::LIMIT => 'LIMIT',
        TradeTypes::MARKET => 'MARKET',
        TradeTypes::STOP_LOSS => 'STOP_LOSS',
        TradeTypes::STOP_LOSS_LIMIT => 'STOP_LOSS_LIMIT',
        TradeTypes::TAKE_PROFIT => 'TAKE_PROFIT',
        TradeTypes::TAKE_PROFIT_LIMIT => 'TAKE_PROFIT_LIMIT',
        TradeTypes::LIMIT_MAKER => 'LIMIT_MAKER'
    ];

    private $tradeSides = [
        TradeTypes::TRADE_BUY => 'BUY',
        TradeTypes::TRADE_SELL => 'SELL'
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
        $startTime = $startTime * 1000;

        try {
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute()."klines",
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
     * @return string
     */
    private function getFuturesAPIBaseRoute() : string
    {
        return "https://fapi.binance.com/fapi/v1/";
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
        $timestamp = time() * 1000;

        $query = [
            'timestamp' => $timestamp
        ];

        $query = $this->addSignature($query);

        try {
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute()."account",
                [
                    'query' => $query,
                    'headers' => $this->getKeyHeader()
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

    /**
     * @param CurrencyPair $currencyPair
     * @param int $side
     * @param float $quantity
     * @return Trade
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function marketTrade(CurrencyPair $currencyPair, int $side, float $quantity): Trade
    {
        $type = $this->tradeTypes[TradeTypes::MARKET];
        $side = $this->tradeSides[$side];
        $timestamp = time() * 1000;

        $query = [
            'symbol' => $currencyPair->getSymbol(),
            'side' => $side,
            'type' => $type,
            'quantity' => $quantity,
            'timestamp' => $timestamp,
        ];

        $result = $this->newOrder($query);
        /** TODO read data and create new trade */
        return new Trade();
    }


    /**
     * @param CurrencyPair $currencyPair
     * @param float $quantity
     * @param float $price
     * @return Trade
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function stopLossTrade(CurrencyPair $currencyPair, float $quantity, float $price): Trade
    {
        $type = $this->tradeTypes[TradeTypes::STOP_LOSS];
        $timestamp = time() * 1000;

        $query = [
            'symbol' => $currencyPair->getSymbol(),
            'type' => $type,
            'side' => $this->tradeSides[TradeTypes::TRADE_SELL],
            'quantity' => $quantity,
            'stopPrice' => $price,
            'timestamp' => $timestamp,
        ];

        $result = $this->newOrder($query);
        /** TODO read data and create new trade */
        return new Trade();
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param float $quantity
     * @param float $price
     * @param float $stopPrice
     * @return Trade
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function stopLossLimitTrade(CurrencyPair $currencyPair, float $quantity, float $price, float $stopPrice): Trade
    {
        $type = $this->tradeTypes[TradeTypes::STOP_LOSS_LIMIT];
        $timestamp = time() * 1000;

        $query = [
            'symbol' => $currencyPair->getSymbol(),
            'type' => $type,
            'side' => $this->tradeSides[TradeTypes::TRADE_SELL],
            'quantity' => $quantity,
            'stopPrice' => $stopPrice,
            'price' => $price,
            'timestamp' => $timestamp,
            'timeInForce' => 'GTC'
        ];

        $result = $this->newOrder($query);
        /** TODO read data and create new trade */
        return new Trade();
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $limit
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getOrderBook(CurrencyPair $currencyPair, int $limit = 100): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute()."depth",
                ['query' => [
                    'symbol' => $currencyPair->getSymbol(),
                    'limit' => $limit
                ]
                ]);
            $rawOrders = $response->toArray();

            $rawBids = $rawOrders['bids'];
            $rawAsks = $rawOrders['asks'];
            $bids = $asks = [];

            foreach ($rawBids as $rawBid) {
                $bid = new BookOrder($currencyPair, BookOrderTypes::BID, $rawBid[0], $rawBid[1]);
                $bids[] = $bid;
            }
            foreach ($rawAsks as $rawAsk) {
                $ask = new BookOrder($currencyPair, BookOrderTypes::ASK, $rawAsk[0], $rawAsk[1]);
                $asks[] = $ask;
            }

            return [$bids, $asks];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return float
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getOpenInterest(CurrencyPair $currencyPair): float
    {
        try {
            $response = $this->httpClient->request('GET', $this->getFuturesAPIBaseRoute()."openInterest",
                ['query' => [
                    'symbol' => $currencyPair->getSymbol()
                ]
                ]);
            $data = $response->toArray();
            return $data['openInterest'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $query
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function newOrder(array $query)
    {
        $query = $this->addSignature($query);
        $response = $this->httpClient->request('POST', $this->getAPIBaseRoute()."order/test",
                [
                    'query' => $query,
                    'headers' => $this->getKeyHeader()
                ]);
        $data = $response->getContent(false);
        return $response->toArray();
    }

    /**
     * @param array $query
     * @return array
     */
    private function addSignature(array $query)
    {
        $secret = $_ENV['BINANCE_SECRET'];
        $totalParams = http_build_query($query);
        $query['signature'] = hash_hmac("sha256", $totalParams, $secret);
        return $query;
    }

    /**
     * @return array
     */
    private function getKeyHeader()
    {
        return  [
            'X-MBX-APIKEY' => $_ENV['BINANCE_KEY']
        ];
    }
}