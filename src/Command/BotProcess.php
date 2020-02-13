<?php


namespace App\Command;


use App\Entity\BotAlgorithm;
use App\Entity\Candle;
use App\Entity\TradeTypes;
use App\Kernel;
use App\Model\BotAlgorithmManager;
use App\Model\CandleManager;
use App\Service\ExternalDataService;
use App\Service\TelegramBot;
use App\Service\TradeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BotProcess extends \Thread
{

    /**
     * @var int
     */
    private $algoId;

    /**
     * @var float
     */
    private $lastPrice;

    /**
     * @var int
     */
    private $lastCandleId;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var bool
     */
    private $debug;

    /**
     * BotProcess constructor.
     * @param int $algoId
     * @param float $lastPrice
     * @param int $lastCandleId
     * @param string $environment
     * @param bool $debug
     */
    public function __construct(int $algoId, float $lastPrice, int $lastCandleId, string $environment, bool $debug)
    {
        $this->algoId = $algoId;
        $this->lastPrice = $lastPrice;
        $this->lastCandleId = $lastCandleId;
        $this->environment = $environment;
        $this->debug = $debug;
    }


    public function run()
    {
        require_once __DIR__.'/../../vendor/autoload.php';
        require_once __DIR__.'/../Kernel.php';


        $kernelInThread = new Kernel($this->environment, $this->debug);
        $kernelInThread->boot();

        $container = $kernelInThread->getContainer();

        /** @var BotAlgorithmManager $algoManager */
        $algoManager = $container->get('App\Model\BotAlgorithmManager');
        /** @var CandleManager $candleManager */
        $candleManager = $container->get('App\Model\CandleManager');

        /** @var BotAlgorithm $algo */
        $algo = $algoManager->getAlgo($this->algoId);

        try {
            $this->log("RUNNING BOT USING ALGO ".$algo->getId()." ".$algo->getName());

            /*if($algoManager->checkStopLossAndTakeProfit($algo, $lastPrice)->isShort()) {
                $this->output->writeln(["NEW SHORT TRADE (STOP LOSS/TAKE PROFIT)"]);
                $this->newOrder($algo, TradeTypes::TRADE_SELL, $lastPrice);
                return;
            }*/

            if($this->lastCandleId != 0) {
                /** @var Candle $lastCandle */
                $lastCandle = $candleManager->getCandle($this->lastCandleId);
                $timeFrameSeconds = $algo->getTimeFrame() * 60;

                if($this->checkTimeFrameClose($lastCandle->getCloseTime(), $timeFrameSeconds)) {
                    $this->log("CHECKING FOR NEW TRADE");

                    $lastOpen = $this->getLastOpen($timeFrameSeconds);
                    $loadFrom = $this->getTimestampToLoadFrom($lastOpen, $timeFrameSeconds);

                    $lastCandles = $candleManager->getCandlesByTimeFrame($algo, $loadFrom, $lastOpen);
                    $result = $algoManager->runAlgo($algo, $lastCandles);

                    if($algo->isLong() && $result->isShort()) {
                        $this->log("NEW SHORT TRADE");
                        $this->newOrder($algo, TradeTypes::TRADE_SELL, $this->lastPrice, $container);
                    } else if($algo->isShort() && $result->isLong()) {
                        $this->log("NEW LONG TRADE");
                        $this->newOrder($algo, TradeTypes::TRADE_BUY, $this->lastPrice, $container);
                    } else {
                        $this->log("NO NEW TRADE");
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
        }

    }

    /**
     * @param string $message
     */
    private function log(string $message)
    {
        try {
            $now = new \DateTime();
            $nowString = $now->format('d-m-Y H:i:s');
            echo "$nowString: $message \n";
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
    private function newOrder(BotAlgorithm $algo, int $tradeType, float $currentPrice, ContainerInterface $container)
    {
        /** @var TradeService $tradeService */
        $tradeService = $container->get('App\Service\TradeService');
        /** @var ExternalDataService $dataService */
        $dataService = $container->get('App\Service\ExternalDataService');
        /** @var TelegramBot $telegramBot */
        $telegramBot = $container->get('App\Service\TelegramBot');
        /** @var BotAlgorithmManager $algoManager */
        $algoManager = $container->get('App\Model\BotAlgorithmManager');

        if($tradeType == TradeTypes::TRADE_BUY) {
            $currencyToUse = $algo->getCurrencyPair()->getSecondCurrency();
            $algo->setLong();
        } else if($tradeType == TradeTypes::TRADE_SELL) {
            $currencyToUse = $algo->getCurrencyPair()->getFirstCurrency();
            $algo->setShort();
        } else return;

        $balance = $dataService->loadBalance($currencyToUse);
        $quantity = $this->calculateQuantity($tradeType, $currentPrice, $balance);

        /** TODO it´s possible that the price changes and the balance is not enough to buy the amount, the trade needs to be created again */
        /*try {
            $trade = $this->tradeService->newMarketTrade($algo->getCurrencyPair(), $tradeType, $quantity);
        } catch (\Exception $exception) {
            $this->output->writeln(["ERROR MAKING TRADE:".$exception->getMessage()]);
        }*/

        $trade = $tradeService->newTestTrade($algo, $tradeType, $currentPrice);

        /** TODO check order has been filled before */
        $telegramBot->sendNewTradeMessage($_ENV['TELEGRAM_USER_ID'], $algo, $trade);
        $algoManager->saveAlgo($algo);
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