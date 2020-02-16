<?php


namespace App\Service;


use App\Entity\BotAlgorithm;
use App\Entity\CurrencyPair;
use App\Entity\Trade;
use App\Entity\TradeStatusTypes;
use App\Entity\TradeTypes;
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
     * @param BotAlgorithm $algo
     * @param int $side
     * @param float $currentPrice
     * @return Trade
     * @throws \Exception
     */
    public function newTestTrade(BotAlgorithm $algo, int $side, float $currentPrice)
    {
        if(!$this->checkSide($side)) {
            throw new \Exception();
        }
        $trade = new Trade();
        $trade->setPrice($currentPrice);
        $trade->setType($side);
        $trade->setAlgo($algo);
        $trade->setOrderId(999);
        $trade->setAmount(0);
        $trade->setTimeStamp(time());
        $trade->setStatus(TradeStatusTypes::FILLED);
        $trade->setMode($algo->getMode());

        $this->entityManager->persist($trade);
        $this->entityManager->flush();

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