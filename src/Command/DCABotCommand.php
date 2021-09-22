<?php


namespace App\Command;


use App\Entity\Algorithm\AlgoModes;
use App\Entity\Algorithm\BotAccount;
use App\Entity\Data\TimeFrames;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\API\APIException;
use App\Model\CandleManager;
use App\Service\Algorithm\BotAccountService;
use App\Service\ThirdPartyAPIs\TelegramBot;
use App\Service\Trade\TradeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DCABotCommand extends Command
{

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:bot:dca';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @param LoggerInterface $botsLogger
     * @param CandleManager $candleManager
     * @param TradeService $tradeService
     * @param TelegramBot $telegramBot
     * @param BotAccountService $botAccountService
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $botsLogger, CandleManager $candleManager, TradeService $tradeService,
                                TelegramBot $telegramBot, BotAccountService $botAccountService)
    {
        $this->entityManager= $entityManager;
        $this->logger = $botsLogger;
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

        $strategy = $botAccount->getDcaStrategy();

        $this->log($botAccount, "RUNNING DCA BOT ".$botAccount->getId()." ".$strategy->getDescription());

        if(!$strategy || !$strategy->readyForNewTrade()) {
            $this->log($botAccount, "TRADE ALREADY TAKEN IN PERIOD");
            return;
        }

        try {
            $lastOpen = $this->getLastOpen(TimeFrames::TIMEFRAME_1D * 60);
            $lastDailyCandle = $this->candleManager->getCandleByTime($strategy->getCurrencyPair(), $lastOpen);

            $percentageChange = (($lastPrice / $lastDailyCandle->getClosePrice()) - 1) * 100;

            if($percentageChange < 0 && abs($percentageChange) > $strategy->getDipPercentage()) {
                $this->newDCAOrder($botAccount, $lastPrice);
            } else if($strategy->getLastTimestampToBuy() < time()) {
                $this->newDCAOrder($botAccount, $lastPrice);
            } else {
                $this->log($botAccount, "NO NEW TRADE");
            }
        } catch (\Exception $exception) {
            $this->log($botAccount, "ERROR:".$exception->getMessage());
        }

    }

    /**
     * @param BotAccount $botAccount
     * @param float $currentPrice
     * @throws \App\Exceptions\API\APINotFoundException
     */
    private function newDCAOrder(BotAccount $botAccount, float $currentPrice)
    {
        $this->log($botAccount, "NEW DCA TRADE");

        $strategy = $botAccount->getDcaStrategy();

        $quantity = $this->calculateQuantity($currentPrice, $strategy->getTradeAmount());

        $this->log($botAccount, "QUANTITY: $quantity, PRICE: $currentPrice");

        if($strategy->getMode() == AlgoModes::TESTING) {
            $trade = $this->tradeService->newTestTrade($botAccount, TradeTypes::TRADE_BUY, $currentPrice, $quantity);

            $strategy->setLastTrade($trade);
            $this->entityManager->persist($strategy);
            $this->tradeService->saveTrade($trade);
        } else if($strategy->getMode() == AlgoModes::LIVE) {
            try {
                $trade = $this->tradeService->newMarketTrade($botAccount, $strategy->getCurrencyPair(), TradeTypes::TRADE_BUY, $quantity);
                $trade->setBotAccount($botAccount);
                $trade->setMode($botAccount->getMode());
                $trade->setPrice($currentPrice);

                $strategy->setLastTrade($trade);
                $this->entityManager->persist($strategy);
                $this->tradeService->saveTrade($trade);

                $this->telegramBot->sendNewDCATradeMessage($botAccount, $trade);

                $this->entityManager->persist($botAccount);

            } catch (APIException $apiException) {
                $this->log($botAccount, "ERROR MAKING TRADE: $apiException");
                $this->telegramBot->sendNewErrorMessage($_ENV['TELEGRAM_USER_ID'], $strategy->getDescription(), $apiException);
            }
        }
    }

    /**
     * @param BotAccount $botAccount
     * @param string $message
     * @param array $context
     */
    private function log(BotAccount $botAccount, string $message, $context = [])
    {
        try {
            $now = new \DateTime();
            $nowString = $now->format('d-m-Y H:i:s');
            $this->logger->info("$nowString: (bot {$botAccount->getId()} dca strategy) -> $message", $context);
        } catch (\Exception $ex) {}
    }


    /**
     * @param float $price
     * @param float $balance
     * @return float
     */
    private function calculateQuantity(float $price, float $balance)
    {
        $quantity = ($balance / $price);
        return floor($quantity * 100000) / 100000;
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
