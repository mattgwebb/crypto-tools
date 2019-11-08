<?php


namespace App\Service;


use App\Entity\CurrencyPair;
use App\Entity\Trade;
use App\Entity\TradeTypes;

class TradeService
{
    /**
     * @var ApiFactory
     */
    private $apiFactory;

    /**
     * TradeService constructor.
     * @param ApiFactory $apiFactory
     */
    public function __construct(ApiFactory $apiFactory)
    {
        $this->apiFactory = $apiFactory;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $side
     * @param float $quantity
     * @return Trade
     * @throws \Exception
     */
    public function newMarketTrade(CurrencyPair $currencyPair, int $side, float $quantity)
    {
        if(!$this->checkSide($side)) {
            throw new \Exception();
        }
        $api = $this->getAPI($currencyPair);
        if(!$api) {
            throw new \Exception();
        }

        return $api->marketTrade($currencyPair, $side, $quantity);
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param float $quantity
     * @param float $price
     * @param float $stopPrice
     * @return Trade
     * @throws \Exception
     */
    public function newStopLossLimitTrade(CurrencyPair $currencyPair, float $quantity, float $price, float $stopPrice)
    {
        $api = $this->getAPI($currencyPair);
        if(!$api) {
            throw new \Exception();
        }

        return $api->stopLossLimitTrade($currencyPair, $quantity, $price, $stopPrice);
    }

    /**
     * @param CurrencyPair $pair
     * @return ApiInterface|bool
     */
    private function getAPI(CurrencyPair $pair)
    {
        return $this->apiFactory->getApi($pair->getFirstCurrency()->getExchange());
    }

    /**
     * @param int $side
     * @return bool
     */
    private function checkSide(int $side)
    {
        return in_array($side, [TradeTypes::TRADE_BUY, TradeTypes::TRADE_SELL]);
    }
}