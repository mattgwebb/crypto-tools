<?php


namespace App\Service\Trade;


use App\Entity\Algorithm\AlgoModes;
use App\Entity\Algorithm\BotAccount;
use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\CurrencyPair;
use App\Entity\Trade\BotAccountHistoricalPortfolio;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeStatusTypes;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\API\APIException;
use App\Exceptions\API\APINotFoundException;
use App\Repository\Trade\TradeRepository;
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
     * @var TradeRepository
     */
    private $tradeRepository;

    /**
     * TradeService constructor.
     * @param ApiFactory $apiFactory
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ApiFactory $apiFactory, EntityManagerInterface $entityManager, TradeRepository $tradeRepository)
    {
        $this->apiFactory = $apiFactory;
        $this->entityManager = $entityManager;
        $this->tradeRepository = $tradeRepository;
    }

    /**
     * @param BotAccount $botAccount
     * @param CurrencyPair $currencyPair
     * @param int $side
     * @param float $quantity
     * @return Trade
     * @throws APIException
     * @throws APINotFoundException
     */
    public function newMarketTrade(BotAccount $botAccount, CurrencyPair $currencyPair, int $side, float $quantity)
    {
        if(!$this->checkSide($side)) {
            throw new \Exception();
        }
        $api = $this->getAPI($botAccount, $currencyPair);
        if(!$api) {
            throw new APINotFoundException();
        }

        if($botAccount->isMargin()) {
            if($side == TradeTypes::TRADE_BUY) {
                $quantity *= $botAccount->getLeverage();
            }
            return $api->marketMarginTrade($currencyPair, $side, $quantity);
        } else {
            return $api->marketTrade($currencyPair, $side, $quantity);
        }
    }

    /**
     * @param BotAccount $botAccount
     * @param int $side
     * @param float $currentPrice
     * @param float $amount
     * @return Trade
     * @throws \Exception
     */
    public function newTestTrade(BotAccount $botAccount, int $side, float $currentPrice, float $amount)
    {
        if($botAccount->isMargin()) {
            $amount *= $botAccount->getLeverage();
        }

        if(!$this->checkSide($side)) {
            throw new \Exception();
        }
        $trade = new Trade();
        $trade->setPrice($currentPrice);
        $trade->setFillPrice($currentPrice);
        $trade->setType($side);
        $trade->setBotAccount($botAccount);
        $trade->setOrderId(999);
        $trade->setAmount($amount);
        $trade->setTimeStamp(time());
        $trade->setStatus(TradeStatusTypes::FILLED);
        $trade->setMode(AlgoModes::TESTING);

        $this->saveTrade($trade);

        return $trade;
    }

    /**
     * @param BotAccount $botAccount
     * @param CurrencyPair $currencyPair
     * @param float $quantity
     * @param float $price
     * @param float $stopPrice
     * @return Trade
     * @throws \Exception
     */
    public function newStopLossLimitTrade(BotAccount $botAccount, CurrencyPair $currencyPair, float $quantity, float $price, float $stopPrice)
    {
        $api = $this->getAPI($botAccount, $currencyPair);
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
     * @param BotAccount $botAccount
     * @param float $currentPrice
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function calculateBotAccountPnL(BotAccount $botAccount, float $currentPrice)
    {
        $lastTrade = $this->tradeRepository->getBotAccountLastTrade($botAccount);

        if($lastTrade) {
            $dailyPortfolioData = new BotAccountHistoricalPortfolio();
            $dailyPortfolioData->setBotAccount($botAccount);
            $dailyPortfolioData->setTimeStamp(time());

            $portfolioLeveragedValue = $lastTrade->getAmount() * $lastTrade->getFillPrice();
            $portfolioValue = $portfolioLeveragedValue / $botAccount->getLeverage();

            if($lastTrade->getType() == TradeTypes::TRADE_BUY) {
                $currentLeveragedValue = $lastTrade->getAmount() * $currentPrice;
                $currentValue = $portfolioValue + ($currentLeveragedValue - $portfolioLeveragedValue);

                $dailyPortfolioData->setTotalValue($currentValue);
                $dailyPortfolioData->setPnlAmount($currentValue - $portfolioValue);
                $dailyPortfolioData->setPnlPercentage((($currentValue / $portfolioValue) - 1) * 100);
            } else {
                $dailyPortfolioData->setTotalValue($portfolioValue);
            }
            $this->entityManager->persist($dailyPortfolioData);
            $this->entityManager->flush();
        }
    }

    /**
     * @param BotAccount $botAccount
     * @param CurrencyPair $pair
     * @return ApiInterface|bool
     */
    private function getAPI(BotAccount $botAccount, CurrencyPair $pair)
    {
        $api = $this->apiFactory->getApi($pair->getFirstCurrency()->getExchange());
        $api->setBotAccountId($botAccount->getId());

        return $api;
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