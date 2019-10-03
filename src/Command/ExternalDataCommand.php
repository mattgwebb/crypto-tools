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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExternalDataCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:get-data';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * ExternalDataCommand constructor.
     * @param BinanceAPI $api
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exchanges = $this->entityManager
            ->getRepository(Exchange::class)
            ->findAll();

        /** @var Exchange $exchange */
        foreach($exchanges as $exchange) {
            $currencies = $exchange->getCurrencies();
            $api = ApiFactory::getApi($exchange);

            /** @var Currency $currency */
            foreach($currencies as $currency) {
                $this->loadNewCandles($api, $currency, $output);
            }
        }
    }

    /**
     * @param ApiInterface $api
     * @param Currency $currency
     * @param OutputInterface $output
     */
    private function loadNewCandles(ApiInterface $api, Currency $currency, OutputInterface $output)
    {
        /** @var Candle $lastCandle */
        $lastCandle = $this->entityManager
            ->getRepository(Candle::class)
            ->findLast($currency);

        if(!$lastCandle) {
            $lastTime = time() - 10368000; //100 days
        } else {
            $lastTime = $lastCandle->getCloseTime();
        }

        $totalCandles = 0;

        $candles = $api->getCandles($currency, TimeFrames::TIMEFRAME_4H, $lastTime);

        /** @var Candle $candle */
        foreach($candles as $candle) {
            if($candle->getCloseTime() < time()) {
                $this->entityManager->persist($candle);
                $totalCandles ++;
            }
        }
        $this->entityManager->flush();
        $output->writeln([
            get_class($api),
            $currency->getSymbol(),
            "new candles: $totalCandles",
        ]);
    }
}
