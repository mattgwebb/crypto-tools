<?php


namespace App\Command;


use App\Entity\BotAlgorithm;
use App\Entity\Candle;
use App\Entity\CurrencyPair;
use App\Entity\StrategyResult;
use App\Entity\TimeFrames;
use App\Entity\TradeTypes;
use App\Model\BotAlgorithmManager;
use App\Repository\CurrencyPairRepository;
use App\Service\ExternalDataService;
use App\Service\TradeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class BotCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:run-bot';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TradeService
     */
    private $tradeService;

    /**
     * @var ExternalDataService
     */
    private $dataService;

    /**
     * @var BotAlgorithmManager
     */
    private $algoManager;

    /**
     * BotCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param TradeService $tradeService
     * @param ExternalDataService $dataService
     * @param BotAlgorithmManager $algoManager
     */
    public function __construct(EntityManagerInterface $entityManager, TradeService $tradeService,
                                ExternalDataService $dataService, BotAlgorithmManager $algoManager)
    {
        $this->entityManager = $entityManager;
        $this->tradeService = $tradeService;
        $this->dataService = $dataService;
        $this->algoManager = $algoManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('algo_id', InputArgument::REQUIRED, 'Algo id');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var BotAlgorithm $algo */
        $algo = $this->entityManager
            ->getRepository(BotAlgorithm::class)
            ->find($input->getArgument('algo_id'));

        if(!$algo) {
            $output->writeln([
                "ERROR",
                "Algo not found"
            ]);
            return;
        }

        /** @var int $newCandles */
        /** @var Candle $lastCandle */
        /** @var int $lastPrice */
        list($newCandles, $lastCandle, $lastPrice) = $this->dataService->loadPairNewCandles($algo->getCurrencyPair());

        /** TODO check stop loss/ take profit always on new price update (every execution) */
        /** TODO stop loss or take profit programmed (only possible 1 of 2) or created on the fly? */

        if($newCandles > 0) {
            $timeFrameSeconds = $algo->getTimeFrame() * 60;
            if($this->checkTimeFrameClose($lastCandle->getCloseTime(), $timeFrameSeconds)) {
                $lastOpen = $this->getLastOpen($timeFrameSeconds);
                $loadFrom = $this->getTimestampToLoadFrom($lastOpen, $timeFrameSeconds);

                /** @var CurrencyPairRepository $currencyPairRepo */
                $currencyPairRepo =  $this->entityManager->getRepository(CurrencyPair::class);

                $lastCandles = $currencyPairRepo
                    ->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame(), $loadFrom, $lastOpen);
                $result = $this->algoManager->runAlgo($algo, $lastCandles);

                if($algo->isLong() && $result->getTradeResult() == StrategyResult::TRADE_SHORT) {
                    $this->newOrder($algo, TradeTypes::TRADE_SELL);
                } else if($algo->isShort() && $result->getTradeResult() == StrategyResult::TRADE_LONG) {
                    $this->newOrder($algo, TradeTypes::TRADE_BUY);
                }
            }
        }
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $tradeType
     */
    private function newOrder(BotAlgorithm $algo, int $tradeType)
    {
        if($tradeType == TradeTypes::TRADE_BUY) {
            $algo->setLong();
        } else if($tradeType == TradeTypes::TRADE_SELL) {
            $algo->setShort();
        } else return;

        //$this->tradeService->newMarketTrade($algo->getCurrencyPair(), $tradeType, 0.1);

        $this->entityManager->persist($algo);
        $this->entityManager->flush();
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
