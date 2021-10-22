<?php


namespace App\Service\Exchanges;

use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\TimeFrames;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeStatusTypes;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\API\APIException;


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

    private $tradeTypes = [
        TradeTypes::MARKET => 'market'
    ];

    private $tradeSides = [
        TradeTypes::TRADE_BUY => 'buy',
        TradeTypes::TRADE_SELL => 'sell'
    ];

    private $tradeStatuses = [
        TradeStatusTypes::FILLED => "closed"
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
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute()."public/OHLC",
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
        return "https://api.kraken.com/0/";
    }

    /**
     * @return string
     */
    protected function getMarginAPIBaseRoute() : string
    {
        return "https://api.kraken.com/0/";
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param $rawData
     * @param int $timeframe
     * @return Candle
     */
    protected function getCandleFromRawData(CurrencyPair $currencyPair, $rawData, int $timeframe): Candle
    {
        $candle = new Candle();
        $candle->setOpenTime($rawData[0]);
        $candle->setOpenPrice($rawData[1]);
        $candle->setHighPrice($rawData[2]);
        $candle->setLowPrice($rawData[3]);
        $candle->setClosePrice($rawData[4]);
        $candle->setVolume($rawData[6]);
        $candle->setCloseTime($rawData[0] + ($timeframe * 60) - 1);
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
        $timestamp = (int)round(microtime(true) * 1000);

        $data = [
            'nonce' => $timestamp
        ];

        $url = '/private/Balance';

        $response = $this->httpClient->request('POST', $this->getAPIBaseRoute().$url,
            [
                'body' => $data,
                'headers' => $this->getHeaders($data, '/0'.$url)
            ]);

        $data = $response->toArray();

        $balances = [];

        if(isset($data['result'])) {
            foreach($data['result'] as $asset => $balance) {
                $balances[$asset] = [
                    "free" => $balance,
                    "netAsset" => $balance
                ];
            }
        }
        return $balances;
    }

    public function getUserMarginBalance(): array
    {
        // TODO: Implement getUserMarginBalance() method.
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
        $type = $this->tradeTypes[TradeTypes::MARKET];
        $apiSide = $this->tradeSides[$side];
        $timestamp = (int)round(microtime(true) * 1000);

        $data = [
            'pair' => $currencyPair->getSymbol(),
            'type' => $apiSide,
            'ordertype' => $type,
            'volume' => $quantity,
            'nonce' => $timestamp
        ];


        $trade = $this->newOrder($data);
        $trade->setType($side);
        return $trade;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $side
     * @param float $quantity
     * @return Trade
     */
    public function marketMarginTrade(CurrencyPair $currencyPair, int $side, float $quantity): Trade
    {
        // TODO: Implement marketMarginTrade() method.
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

    /**
     * @param array $data
     * @param bool $margin
     * @return Trade
     */
    private function newOrder(array $data, bool $margin = false)
    {
        $baseAPI = $this->getAPIBaseRoute();

        $url = '/private/AddOrder';

        $response = $this->httpClient->request('POST', $baseAPI.$url,
            [
                'body' => $data,
                'headers' => $this->getHeaders($data, '/0'.$url)
            ]);
        //$this->checkForError($response);

        $result = $response->toArray();

        $txid = $result['result']['txid'][0];

        sleep(1);

        return  $this->getOrderInfo($txid);
    }

    /**
     * @param string $txid
     * @return Trade
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function getOrderInfo(string $txid)
    {
        $timestamp = (int)round(microtime(true) * 1000);

        $url = '/private/QueryOrders';

        $data = [
            'nonce' => $timestamp,
            'txid' => $txid
        ];

        $response = $this->httpClient->request('POST', $this->getAPIBaseRoute().$url,
            [
                'body' => $data,
                'headers' => $this->getHeaders($data, '/0'.$url)
            ]);

        $result = $response->toArray();

        $trade = new Trade();

        $trade->setOrderId($txid);

        if(isset($result['result'][$txid])) {
            $trade->setAmount($result['result'][$txid]['vol_exec']);
            $trade->setFillPrice($result['result'][$txid]['price']);
            $trade->setFees(0);
            $trade->setTimeStamp((int)$result['result'][$txid]['closetm']);
            $trade->setStatus($this->getInternalTradeStatus($result['result'][$txid]['status']));
        }
        return $trade;
    }

    /**
     * @param array $data
     * @param string $url
     * @return string
     */
    private function getSignature(array $data, string $url)
    {
        $secret = $_ENV["KRAKEN_BOT_{$this->getBotAccountId()}_SECRET"];
        $totalParams = http_build_query($data);
        $paramsWithNonce = $data['nonce'] . $totalParams;
        $message = $url.hash('sha256', $paramsWithNonce, true);
        return base64_encode(hash_hmac("sha512", $message, base64_decode($secret), true));
    }

    /**
     * @param array $data
     * @param string $url
     * @return array
     */
    private function getHeaders(array $data, string $url)
    {
        return  [
            'API-Key' => $_ENV["KRAKEN_BOT_{$this->getBotAccountId()}_KEY"],
            'API-Sign' => $this->getSignature($data, $url)
        ];
    }

    /**
     * @param string $status
     * @return int
     */
    private function getInternalTradeStatus(string $status)
    {
        foreach($this->tradeStatuses as $key => $string) {
            if($string == $status) {
                return $key;
            }
        }
        return TradeStatusTypes::UNKNOWN;
    }
}