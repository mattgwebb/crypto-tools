<?php


namespace App\Command;

use App\Entity\Candle;
use App\Entity\Currency;
use App\Service\BinanceAPI;
use App\Service\Indicators;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExternalDataCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:get-data';

    /** @var BinanceAPI */
    private $api;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * ExternalDataCommand constructor.
     * @param BinanceAPI $api
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(BinanceAPI $api, EntityManagerInterface $entityManager)
    {
        $this->api = $api;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currencies = $this->entityManager
            ->getRepository(Currency::class)
            ->findAll();

        /** @var Currency $currency */
        foreach($currencies as $currency) {
            //$this->loadNewCandles($currency, $output);

            $candles = $currency->getCandles()->toArray();

            $indicators = new Indicators();
            $data = $indicators->prepareData($candles);

            $bollingerResult = $indicators->bollingerBands($data);
            $rsiResult = $indicators->rsi($data);
            $macdResult = $indicators->macd($data);

            $output->writeln([
                '****************************************',
                $currency->getSymbol(),
                json_encode($bollingerResult),
                json_encode($rsiResult),
                json_encode($macdResult),
                '****************************************',
            ]);

        }
    }

    /**
     * @param Currency $currency
     * @param OutputInterface $output
     * @throws \Exception
     */
    private function loadNewCandles(Currency $currency, OutputInterface $output)
    {
        /** @var Candle $lastCandle */
        $lastCandle = $this->entityManager
            ->getRepository(Candle::class)
            ->findLast($currency);

        $totalCandles = 0;

        $candles = $this->api->getCandles($currency, '4h', $lastCandle->getCloseTime() * 1000);

        /** @var Candle $candle */
        foreach($candles as $candle) {
            if($candle->getCloseTime() < time()) {
                $this->entityManager->persist($candle);
                $totalCandles ++;
            }
        }
        $this->entityManager->flush();
        $output->writeln([
            $currency->getSymbol(),
            "new candles: $totalCandles",
        ]);
    }
}
