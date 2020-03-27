<?php


namespace App\Service\Trade;


use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\CurrencyPair;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeStatusTypes;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\API\APIException;
use App\Exceptions\API\APINotFoundException;
use App\Service\Exchanges\ApiFactory;
use App\Service\Exchanges\ApiInterface;
use Doctrine\ORM\EntityManagerInterface;

class TradeService
{
    /**
     * @var ApiFactory
     */
    private $apiFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * TradeService constructor.
     * @param ApiFactory $apiFactory
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ApiFactory $apiFactory, EntityManagerInterface $entityManager)
    {
        $this->apiFactory = $apiFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $side
     * @param float $quantity
     * @return Trade
     * @throws APINotFoundException
     * @throws APIException
     */
    public function newMarketTrade(CurrencyPair $currencyPair, int $side, float $quantity)
    {
        if(!$this->checkSide($side)) {
            throw new \Exception();
        }
        $api = $this->getAPI($currencyPair);
        if(!$api) {
            throw new APINotFoundException();
        }

        return $api->marketTrade($currencyPair, $side, $quantity);
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $side
     * @param float $currentPrice
     * @param float $amount
     * @return Trade
     * @throws \Exception
     */
    public function newTestTrade(BotAlgorithm $algo, int $side, float $currentPrice, float $amount)
    {
        if(!$this->checkSide($side)) {
            throw new \Exception();
        }
        $trade = new Trade();
        $trade->setPrice($currentPrice);
        $trade->setFillPrice($currentPrice);
        $trade->setType($side);
        $trade->setAlgo($algo);
        $trade->setOrderId(999);
        $trade->setAmount($amount);
        $trade->setTimeStamp(time());
        $trade->setStatus(TradeStatusTypes::FILLED);
        $trade->setMode($algo->getMode());

        $this->saveTrade($trade);

        return $trade;
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
     * @param Trade $trade
     */
    public function saveTrade(Trade $trade)
    {
        $this->entityManager->persist($trade);
        $this->entityManager->flush();
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