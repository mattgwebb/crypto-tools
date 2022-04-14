<?php


namespace App\Service\Exchanges;

use App\Entity\Data\TimeFrames;
use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeStatusTypes;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\API\APIException;
use Symfony\Contracts\HttpClient\ResponseInterface;


class KucoinAPI extends ApiInterface
{

    private $tradeTypes = [
        TradeTypes::LIMIT => 'limit',
        TradeTypes::MARKET => 'market',
    ];

    private $tradeSides = [
        TradeTypes::TRADE_BUY => 'buy',
        TradeTypes::TRADE_SELL => 'sell'
    ];

    private $tradeStatuses = [
        TradeStatusTypes::FILLED => "DEAL",
        TradeStatusTypes::CANCELED => "CANCEL",
    ];

    protected $timeFrames = [
        TimeFrames::TIMEFRAME_5M => '5min',
        TimeFrames::TIMEFRAME_15M => '15min',
        TimeFrames::TIMEFRAME_30M => '30min',
        TimeFrames::TIMEFRAME_45M => '45min',
        TimeFrames::TIMEFRAME_1H => '1hour',
        TimeFrames::TIMEFRAME_2H => '2hour',
        TimeFrames::TIMEFRAME_3H => '3hour',
        TimeFrames::TIMEFRAME_4H => '4hour',
        TimeFrames::TIMEFRAME_1D > '1day',
        TimeFrames::TIMEFRAME_1W => '1week'
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
     * TODO api sometimes returns error, make request again
     */
    protected function getCandlesData(CurrencyPair $currencyPair, $timeFrame, $startTime) : array
    {
        try {
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute()."/api/v1/market/candles",
                ['query' => [
                    'type' => $timeFrame,
                    'symbol' => $currencyPair->getSymbol(),
                    'startAt' => $startTime,
                ]
                ]);
            $rawCandles = $response->toArray();
        } catch (\Exception $e) {
            throw $e;
        }

        return $rawCandles['data'];
    }

    /**
     * @return string
     */
    protected function getAPIBaseRoute() : string
    {
        return "https://api.kucoin.com";
    }

    /**
     * @return string
     */
    protected function getMarginAPIBaseRoute() : string
    {
        return "https://api.kucoin.com";
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
        $candle->setClosePrice($rawData[2]);
        $candle->setHighPrice($rawData[3]);
        $candle->setLowPrice($rawData[4]);
        $candle->setVolume($rawData[5]);
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
        $subAccountId = $this->getSubAccountId();

        try {
            $requestPath = "/api/v1/sub-accounts/$subAccountId";
            $method = 'GET';

            $response = $this->httpClient->request($method, $this->getAPIBaseRoute()."$requestPath",
                [
                    'headers' => $this->getAuthenticationHeaders($requestPath, '', $method, true)
                ]);
            $data = $response->toArray();
        } catch (\Exception $e) {
            throw $e;
        }

        $balance = [];
        if(isset($data['data']['tradeAccounts'])) {
            foreach($data['data']['tradeAccounts'] as $currencyBalance) {
                $balance[$currencyBalance['currency']] = [
                    "free" => $currencyBalance['available'],
                    "netAsset" => $currencyBalance['available']
                ];
            }
        }
        return $balance;
    }

    /**
     * @return array
     */
    public function getUserMarginBalance(): array
    {
        return [];
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
     * @throws APIException
     */
    public function marketTrade(CurrencyPair $currencyPair, int $side, float $quantity): Trade
    {
        $type = $this->tradeTypes[TradeTypes::MARKET];
        $apiSide = $this->tradeSides[$side];
        $timestamp = (int)round(microtime(true) * 1000);

        $data = [
            'clientOid' => "bot$timestamp",
            'side' => $apiSide,
            'symbol' => $currencyPair->getSymbol(),
            'type' => $type,
            'size' => $quantity,
        ];

        $requestPath = "/api/v1/orders";
        $method = 'POST';

        $response = $this->httpClient->request($method, $this->getAPIBaseRoute()."$requestPath",
            [
                'json' => $data,
                'headers' => $this->getAuthenticationHeaders($requestPath, json_encode($data), $method)
            ]);

        $this->checkForError($response);

        $result = $response->toArray();

        $txid = $result['data']['orderId'];

        sleep(1);

        $trade = $this->getOrderInfo($txid);
        $trade->setType($side);
        return $trade;
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
        $requestPath = "/api/v1/orders/$txid";
        $method = 'GET';

        $response = $this->httpClient->request($method, $this->getAPIBaseRoute()."$requestPath",
            [
                'headers' => $this->getAuthenticationHeaders($requestPath, '', $method)
            ]);

        $result = $response->toArray();

        $trade = new Trade();

        $trade->setOrderId($result['data']['id']);
        $trade->setAmount($result['data']['size']);
        $trade->setFillPrice($result['data']['price']);
        $trade->setFees($result['data']['fee']);
        $trade->setTimeStamp((int)($result['data']['createdAt']/1000));
        $trade->setStatus($this->getInternalTradeStatus($result['data']['opType']));

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
        return new Trade();
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $limit
     * @return array
     */
    public function getOrderBook(CurrencyPair $currencyPair, int $limit = 100): array
    {
       return [];
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return float
     */
    public function getOpenInterest(CurrencyPair $currencyPair): float
    {
        return 0;
    }


    /**
     * @param string $requestPath
     * @param string $body
     * @param string $method
     * @param bool $master
     * @return array
     */
    private function getAuthenticationHeaders(string $requestPath, string $body, string $method, bool $master = false)
    {
        $accountType = $master ? 'MASTER' : 'SUB';

        $secret = $_ENV["KUCOIN_BOT_{$this->getBotAccountId()}_{$accountType}_SECRET"];
        $passphrase = $_ENV["KUCOIN_BOT_{$this->getBotAccountId()}_{$accountType}_PASSPHRASE"];

        $timestamp = (int)round(microtime(true) * 1000);

        $string = $timestamp . $method . $requestPath . $body;
        $signature = base64_encode(hash_hmac("sha256", $string, $secret, true));

        $passphrase = base64_encode(hash_hmac("sha256", $passphrase, $secret, true));

        return  [
            'KC-API-KEY' => $_ENV["KUCOIN_BOT_{$this->getBotAccountId()}_{$accountType}_KEY"],
            'KC-API-SIGN' => $signature,
            'KC-API-TIMESTAMP' => $timestamp,
            'KC-API-PASSPHRASE' => $passphrase,
            'KC-API-KEY-VERSION' => '2'
        ];
    }

    /**
     * @return string
     */
    private function getSubAccountId()
    {
        return $_ENV["KUCOIN_BOT_{$this->getBotAccountId()}_ID"];
    }


    /**
     * @param ResponseInterface $response
     * @throws APIException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function checkForError(ResponseInterface $response)
    {
        try {
            $response->getContent();
        } catch (\Exception $ex) {
            $data = $response->toArray(false);
            throw new APIException((int)$data['code'], $data['msg']);
        }
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