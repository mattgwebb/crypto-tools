<?php


namespace App\Command;

use App\Entity\Candle;
use App\Entity\Currency;
use App\Entity\Exchange;
use App\Entity\TimeFrames;
use App\Service\ApiFactory;
use App\Service\ApiInterface;
use App\Service\BinanceAPI;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadHistoricalDataCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:load-historical-data';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Candle
     */
    private $firstCandle;

    /**
     * @var Currency
     */
    private $currency;

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
            ->addArgument('currency_id', InputArgument::REQUIRED, 'Currency id')
            ->addArgument('start_time', InputArgument::REQUIRED, 'Data start time')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->currencyID = $input->getArgument('currency_id');

        try {
            $this->loadData();

            /** @var Exchange $exchange */
            $exchange = $this->currency->getExchange();
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
            $candles = $api->getCandles($this->currency, TimeFrames::TIMEFRAME_5M, $startTime);

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
                        $candle->setCurrency($this->currency);
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
            $this->currency->getSymbol(),
            "new candles: $totalCandles",
        ]);
    }

    /**
     * @throws \Exception
     */
    private function loadData()
    {
        $this->currency = $this->entityManager
            ->getRepository(Currency::class)
            ->find($this->currencyID);

        if(!$this->currency) {
            throw new \Exception("Currency doen´t exist");
        }

        if(!$this->firstCandleID) {
            $this->firstCandle = $this->entityManager
                ->getRepository(Candle::class)
                ->findFirst($this->currency);
            $this->firstCandleID = $this->firstCandle->getId();
        } else {
            $this->firstCandle = $this->entityManager
                ->getRepository(Candle::class)
                ->find($this->firstCandleID);
        }
    }
}
