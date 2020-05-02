<?php


namespace App\Command;


use App\Entity\Algorithm\AlgoModes;
use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\Candle;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\API\APIException;
use App\Model\BotAlgorithmManager;
use App\Model\CandleManager;
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

    const CANDLES_TO_LOAD = 50;

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
                } else {
                    $this->log($algo, "CANDLE NOT CLOSED YET");
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
     * @param array $context
     */
    private function log(BotAlgorithm $algo, string $message, $context = [])
    {
        try {
            $now = new \DateTime();
            $nowString = $now->format('d-m-Y H:i:s');
            $this->logger->info("$nowString: (algo {$algo->getId()}) -> $message", $context);
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

        $this->log($algo, "QUANTITY: $quantity, PRICE: $currentPrice");

        if($algo->getMode() == AlgoModes::TESTING) {
            $this->tradeService->newTestTrade($algo, $tradeType, $currentPrice, $quantity);
            $this->algoManager->saveAlgo($algo);
        } else if($algo->getMode() == AlgoModes::LIVE) {
            /** TODO it´s possible that the price changes and the balance is not enough to buy the amount, the trade needs to be created again */
            try {
                $trade = $this->tradeService->newMarketTrade($algo->getCurrencyPair(), $tradeType, $quantity);
                $trade->setAlgo($algo);
                $trade->setMode($algo->getMode());
                $trade->setPrice($currentPrice);
                $this->tradeService->saveTrade($trade);

                /** TODO check order has been filled before */
                $this->telegramBot->sendNewTradeMessage($_ENV['TELEGRAM_USER_ID'], $algo, $trade);
                $this->algoManager->saveAlgo($algo);

            } catch (APIException $apiException) {
                $this->log($algo, "ERROR MAKING TRADE: $apiException");
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
            $quantity = ($balance / $price) * 0.95;
            return floor($quantity * 1000000) / 1000000;
        } else {
            return floor($balance * 1000000) / 1000000;
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
