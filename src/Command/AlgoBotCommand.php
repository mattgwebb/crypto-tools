<?php


namespace App\Command;


use App\Entity\BotAlgorithm;
use App\Entity\Candle;
use App\Entity\TradeTypes;
use App\Model\BotAlgorithmManager;
use App\Model\CandleManager;
use App\Service\ExternalDataService;
use App\Service\TelegramBot;
use App\Service\TradeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AlgoBotCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:run-bot';

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
     * BotCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param ExternalDataService $dataService
     * @param LoggerInterface $botsLogger
     * @param BotAlgorithmManager $algoManager
     * @param CandleManager $candleManager
     * @param TradeService $tradeService
     * @param TelegramBot $telegramBot
     */
    public function __construct(EntityManagerInterface $entityManager, ExternalDataService $dataService, LoggerInterface $botsLogger,
                                BotAlgorithmManager $algoManager, CandleManager $candleManager, TradeService $tradeService,
                                TelegramBot $telegramBot)
    {
        $this->entityManager= $entityManager;
        $this->dataService = $dataService;
        $this->logger = $botsLogger;
        $this->algoManager = $algoManager;
        $this->candleManager = $candleManager;
        $this->tradeService = $tradeService;
        $this->telegramBot = $telegramBot;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('algo_id', InputArgument::REQUIRED, 'Algo id');
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
        /** @var BotAlgorithm $algo */
        $algo = $this->algoManager->getAlgo($input->getArgument('algo_id'));
        $lastPrice = (float)$input->getArgument('last_price');
        $lastCandleId = (int)$input->getArgument('last_candle_id');

        try {
            $this->log($algo, "RUNNING BOT USING ALGO ".$algo->getId()." ".$algo->getName());

            /*if($this->algoManager->checkStopLossAndTakeProfit($algo, $lastPrice)->isShort()) {
                $this->output->writeln(["NEW SHORT TRADE (STOP LOSS/TAKE PROFIT)"]);
                $this->newOrder($algo, TradeTypes::TRADE_SELL, $lastPrice);
                return;
            }*/

            if($lastCandleId) {
                /** @var Candle $lastCandle */
                $lastCandle = $this->candleManager->getCandle($lastCandleId);
                $timeFrameSeconds = $algo->getTimeFrame() * 60;

                if($this->checkTimeFrameClose($lastCandle->getCloseTime(), $timeFrameSeconds)) {
                    $this->log($algo, "CHECKING FOR NEW TRADE");

                    $lastOpen = $this->getLastOpen($timeFrameSeconds);
                    $loadFrom = $this->getTimestampToLoadFrom($lastOpen, $timeFrameSeconds);

                    $lastCandles = $this->candleManager->getCandlesByTimeFrame($algo, $loadFrom, $lastOpen);
                    $result = $this->algoManager->runAlgo($algo, $lastCandles);

                    if($algo->isLong() && $result->isShort()) {
                        $this->log($algo, "NEW SHORT TRADE");
                        $this->newOrder($algo, TradeTypes::TRADE_SELL, $lastPrice);
                    } else if($algo->isShort() && $result->isLong()) {
                        $this->log($algo, "NEW LONG TRADE");
                        $this->newOrder($algo, TradeTypes::TRADE_BUY, $lastPrice);
                    } else {
                        $this->log($algo, "NO NEW TRADE");
                    }
                }
            } else {
                $this->log($algo, "NO NEW CANDLE");
            }
        } catch (\Exception $exception) {
            $this->log($algo, $exception->getMessage());
        }

    }

    /**
     * @param BotAlgorithm $algo
     * @param string $message
     */
    private function log(BotAlgorithm $algo, string $message)
    {
        try {
            $now = new \DateTime();
            $nowString = $now->format('d-m-Y H:i:s');
            $this->logger->info("$nowString: (algo {$algo->getId()}) -> $message");
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
     * @param BotAlgorithm $algo
     * @param int $tradeType
     * @param float $currentPrice
     * @throws \Exception
     */
    private function newOrder(BotAlgorithm $algo, int $tradeType, float $currentPrice)
    {
        if($tradeType == TradeTypes::TRADE_BUY) {
            $currencyToUse = $algo->getCurrencyPair()->getSecondCurrency();
            $algo->setLong();
        } else if($tradeType == TradeTypes::TRADE_SELL) {
            $currencyToUse = $algo->getCurrencyPair()->getFirstCurrency();
            $algo->setShort();
        } else return;

        $balance = $this->dataService->loadBalance($currencyToUse);
        $quantity = $this->calculateQuantity($tradeType, $currentPrice, $balance);

        /** TODO it´s possible that the price changes and the balance is not enough to buy the amount, the trade needs to be created again */
        /*try {
            $trade = $this->tradeService->newMarketTrade($algo->getCurrencyPair(), $tradeType, $quantity);
        } catch (\Exception $exception) {
            $this->output->writeln(["ERROR MAKING TRADE:".$exception->getMessage()]);
        }*/

        $trade = $this->tradeService->newTestTrade($algo, $tradeType, $currentPrice);

        /** TODO check order has been filled before */
        $this->telegramBot->sendNewTradeMessage($_ENV['TELEGRAM_USER_ID'], $algo, $trade);
        $this->algoManager->saveAlgo($algo);
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
            return round($balance/$price, 5, PHP_ROUND_HALF_DOWN);
        } else {
            return $balance;
        }
    }

    /**
     * @param int $lastClose
     * @param int $timeFrameSeconds
     * @return int
     */
    private function getTimestampToLoadFrom(int $lastClose, int $timeFrameSeconds)
    {
        $timeRange = 50 * $timeFrameSeconds;
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
