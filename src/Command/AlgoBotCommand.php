<?php


namespace App\Command;


use App\Entity\Algorithm\AlgoModes;
use App\Entity\Algorithm\BotAccount;
use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\Candle;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\API\APIException;
use App\Exceptions\API\APINotFoundException;
use App\Model\BotAlgorithmManager;
use App\Model\CandleManager;
use App\Service\Algorithm\BotAccountService;
use App\Service\Data\ExternalDataService;
use App\Service\ThirdPartyAPIs\TelegramBot;
use App\Service\Trade\TradeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AlgoBotCommand extends Command
{

    const CANDLES_TO_LOAD = 400;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:bot:run';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ExternalDataService
     */
    private $dataService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BotAlgorithmManager
     */
    private $algoManager;

    /**
     * @var CandleManager
     */
    private $candleManager;

    /**
     * @var TradeService
     */
    private $tradeService;

    /**
     * @var TelegramBot
     */
    private $telegramBot;

    /**
     * @var BotAccountService
     */
    private $botAccountService;

    /**
     * BotCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param ExternalDataService $dataService
     * @param LoggerInterface $botsLogger
     * @param BotAlgorithmManager $algoManager
     * @param CandleManager $candleManager
     * @param TradeService $tradeService
     * @param TelegramBot $telegramBot
     * @param BotAccountService $botAccountService
     */
    public function __construct(EntityManagerInterface $entityManager, ExternalDataService $dataService, LoggerInterface $botsLogger,
                                BotAlgorithmManager $algoManager, CandleManager $candleManager, TradeService $tradeService,
                                TelegramBot $telegramBot, BotAccountService $botAccountService)
    {
        $this->entityManager= $entityManager;
        $this->dataService = $dataService;
        $this->logger = $botsLogger;
        $this->algoManager = $algoManager;
        $this->candleManager = $candleManager;
        $this->tradeService = $tradeService;
        $this->telegramBot = $telegramBot;
        $this->botAccountService = $botAccountService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('bot_account_id', InputArgument::REQUIRED, 'Bot id');
        $this->addArgument('last_price', InputArgument::REQUIRED, 'Last price');
        $this->addArgument('last_candle_id', InputArgument::OPTIONAL, 'Last candle id');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $botAccount = $this->botAccountService->getBotAccount($input->getArgument('bot_account_id'));
        $lastPrice = (float)$input->getArgument('last_price');
        $lastCandleId = (int)$input->getArgument('last_candle_id');

        $algo = $botAccount->getAlgo();

        try {
            $this->log($botAccount, "RUNNING BOT ".$botAccount->getId()." ".$botAccount->getDescription()." USING ALGO ".$algo->getId()." ".$algo->getName());

            if($lastCandleId) {
                /** @var Candle $lastCandle */
                $lastCandle = $this->candleManager->getCandle($lastCandleId);
                $timeFrameSeconds = $algo->getTimeFrame() * 60;

                if($this->checkTimeFrameClose($lastCandle->getCloseTime(), $timeFrameSeconds)) {
                    $this->log($botAccount, "CHECKING FOR NEW TRADE");

                    $lastOpen = $this->getLastOpen($timeFrameSeconds);
                    $loadFrom = $this->getTimestampToLoadFrom($lastOpen, $timeFrameSeconds);

                    $lastCandles = $this->candleManager->getCandlesByTimeFrame($algo, $loadFrom, $lastOpen);
                    $result = $this->algoManager->runAlgo($botAccount, $lastCandles);

                    if($botAccount->isLong() && $result->isShort()) {
                        $this->log($botAccount, "NEW SHORT TRADE");
                        $this->newOrder($botAccount, TradeTypes::TRADE_SELL, $lastPrice);
                    } else if($botAccount->isShort() && $result->isLong()) {
                        $this->log($botAccount, "NEW LONG TRADE");
                        $this->newOrder($botAccount, TradeTypes::TRADE_BUY, $lastPrice);
                    } else {
                        $this->log($botAccount, "NO NEW TRADE");
                    }
                } else {
                    $this->log($botAccount, "CANDLE NOT CLOSED YET");
                }
            } else {
                $this->log($botAccount, "NO NEW CANDLE");
            }
        } catch (\Exception $exception) {
            $this->log($botAccount, "ERROR:".$exception->getMessage());
        }

    }

    /**
     * @param BotAccount $botAccount
     * @param string $message
     * @param array $context
     */
    private function log(BotAccount $botAccount, string $message, $context = [])
    {
        $algo = $botAccount->getAlgo();
        try {
            $now = new \DateTime();
            $nowString = $now->format('d-m-Y H:i:s');
            $this->logger->info("$nowString: (bot {$botAccount->getId()} algo {$algo->getId()}) -> $message", $context);
        } catch (\Exception $ex) {}
    }

    /**
     * @param int $close
     * @param int $timeFrameSeconds
     * @return bool
     */
    private function checkTimeFrameClose(int $close, int $timeFrameSeconds)
    {
        return ($close + 1) % $timeFrameSeconds == 0;
    }

    /**
     * @param BotAccount $botAccount
     * @param int $tradeType
     * @param float $currentPrice
     * @throws APINotFoundException
     */
    private function newOrder(BotAccount $botAccount, int $tradeType, float $currentPrice)
    {
        $algo = $botAccount->getAlgo();

        if($tradeType == TradeTypes::TRADE_BUY) {
            $currencyToUse = $algo->getCurrencyPair()->getSecondCurrency();
            $botAccount->setLong();
        } else if($tradeType == TradeTypes::TRADE_SELL) {
            $currencyToUse = $algo->getCurrencyPair()->getFirstCurrency();
            $botAccount->setShort();
        } else return;

        $balance = $this->dataService->loadBalance($botAccount, $currencyToUse);
        $quantity = $this->calculateQuantity($tradeType, $currentPrice, $balance);

        $this->log($botAccount, "QUANTITY: $quantity, PRICE: $currentPrice");

        if($botAccount->getMode() == AlgoModes::TESTING) {
            $this->tradeService->newTestTrade($botAccount, $tradeType, $currentPrice, $quantity);

            $this->entityManager->persist($botAccount);
        } else if($botAccount->getMode() == AlgoModes::LIVE) {
            /** TODO it´s possible that the price changes and the balance is not enough to buy the amount, the trade needs to be created again */
            try {
                $trade = $this->tradeService->newMarketTrade($botAccount, $algo->getCurrencyPair(), $tradeType, $quantity);
                $trade->setBotAccount($botAccount);
                $trade->setMode($botAccount->getMode());
                $trade->setPrice($currentPrice);
                $this->tradeService->saveTrade($trade);

                /** TODO check order has been filled before */
                $this->telegramBot->sendNewTradeMessage($_ENV['TELEGRAM_USER_ID'], $algo, $trade);

                $this->entityManager->persist($botAccount);

            } catch (APIException $apiException) {
                $this->log($botAccount, "ERROR MAKING TRADE: $apiException");
                $this->telegramBot->sendNewErrorMessage($_ENV['TELEGRAM_USER_ID'], $algo, $apiException);
            }
        }
    }

    /**
     * @param int $tradeType
     * @param float $price
     * @param float $balance
     * @return float
     */
    private function calculateQuantity(int $tradeType, float $price, float $balance)
    {
        if($tradeType == TradeTypes::TRADE_BUY) {
            $quantity = ($balance / $price) * 0.99;
            return floor($quantity * 100000) / 100000;
        } else {
            return floor($balance * 100000) / 100000;
        }
    }

    /**
     * @param int $lastClose
     * @param int $timeFrameSeconds
     * @param int $candlesToLoad
     * @return int
     */
    private function getTimestampToLoadFrom(int $lastClose, int $timeFrameSeconds, int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        $timeRange = $candlesToLoad * $timeFrameSeconds;
        return $lastClose - $timeRange;
    }

    /**
     * @param int $timeFrameSeconds
     * @return int
     */
    private function getLastOpen(int $timeFrameSeconds)
    {
        $now = time();
        return (int)(floor($now / $timeFrameSeconds) * $timeFrameSeconds);
    }
}
