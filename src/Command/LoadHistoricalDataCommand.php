<?php


namespace App\Command;

use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\Exchange;
use App\Entity\Data\TimeFrames;
use App\Service\Exchanges\ApiFactory;
use App\Service\Exchanges\ApiInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadHistoricalDataCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:data:historical';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Candle
     */
    private $firstCandle;

    /**
     * @var CurrencyPair
     */
    private $currencyPair;

    /**
     * @var int
     */
    private $currencyID;

    /**
     * @var int
     */
    private $firstCandleID;

    /**
     * LoadHistoricalDataCommand constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            // ...
            ->addArgument('currency_pair_id', InputArgument::REQUIRED, 'Currency pair id')
            ->addArgument('start_time', InputArgument::REQUIRED, 'Data start time')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->currencyID = $input->getArgument('currency_pair_id');

        try {
            $this->loadData();

            /** @var Exchange $exchange */
            $exchange = $this->currencyPair->getFirstCurrency()->getExchange();
            $api = ApiFactory::getApi($exchange);
            $this->loadCandles($api, $input->getArgument('start_time'), $output);

        } catch (\Exception $exception) {
            $output->writeln([
                "Error",
                $exception->getMessage(),
                $exception->getTraceAsString()
            ]);
        }
    }

    /**
     * @param ApiInterface $api
     * @param $startTime
     * @param OutputInterface $output
     * @throws \Exception
     */
    private function loadCandles(ApiInterface $api, $startTime, OutputInterface $output)
    {
        if($this->firstCandle && $this->firstCandle->getCloseTime() < $startTime) {
            $output->writeln([
                "Candles already loaded for this timeframe."
            ]);
            return;
        }

        $totalCandles = 0;
        $continueLoading = true;

        while($continueLoading) {
            $candles = $api->getCandles($this->currencyPair, TimeFrames::TIMEFRAME_5M, $startTime);

            if($candles->isEmpty()) {
                $output->writeln([
                    "Empty candles."
                ]);
                $continueLoading = false;
            } else {
                /** @var Candle $candle */
                foreach($candles as $candle) {
                    if($candle->getCloseTime() == $this->firstCandle->getCloseTime() || $candle->getCloseTime() > time()) {
                        $output->writeln([
                            "Candle aready exists or is latest."
                        ]);
                        $continueLoading = false;
                        break;
                    }
                    if($candle->getCloseTime() < time()) {
                        $candle->setCurrencyPair($this->currencyPair);
                        $this->entityManager->persist($candle);
                        $totalCandles ++;

                        if(($totalCandles % 100) == 0) {
                            $this->entityManager->flush();
                            $this->entityManager->clear();
                            $this->loadData();
                        }
                    }
                }
                $startTime = $candle->getCloseTime();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        $output->writeln([
            get_class($api),
            $this->currencyPair->getSymbol(),
            "new candles: $totalCandles",
        ]);
    }

    /**
     * @throws \Exception
     */
    private function loadData()
    {
        $this->currencyPair = $this->entityManager
            ->getRepository(CurrencyPair::class)
            ->find($this->currencyID);

        if(!$this->currencyPair) {
            throw new \Exception("Currency pair doen´t exist");
        }

        if(!$this->firstCandleID) {
            $this->firstCandle = $this->entityManager
                ->getRepository(Candle::class)
                ->findFirst($this->currencyPair);
            $this->firstCandleID = $this->firstCandle->getId();
        } else {
            $this->firstCandle = $this->entityManager
                ->getRepository(Candle::class)
                ->find($this->firstCandleID);
        }
    }
}
