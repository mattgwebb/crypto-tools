<?php


namespace App\Command;

use App\Entity\Candle;
use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Entity\Exchange;
use App\Entity\TimeFrames;
use App\Service\ApiFactory;
use App\Service\ApiInterface;
use App\Service\BinanceAPI;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExternalDataCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:get-latest-data';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * ExternalDataCommand constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;

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
            //$this->loadBalances($api, $currencies);

            /** @var Currency $currency */
            foreach($currencies as $currency) {
                foreach ($currency->getPairs() as $pair) {
                    $this->loadNewCandles($api, $pair, $output);
                }
            }
        }
    }

    private function loadBalances(ApiInterface $api, $currencies)
    {
        $rawBalances = $api->getUserBalance();

        /** @var Currency $currency */
        foreach($currencies as $currency) {
            if(isset($rawBalances[$currency->getSymbol()])) {
                $currency->setBalance($rawBalances[$currency->getSymbol()]);
                $this->entityManager->persist($currency);
            }
        }
    }

    /**
     * @param ApiInterface $api
     * @param CurrencyPair $pair
     * @param OutputInterface $output
     */
    private function loadNewCandles(ApiInterface $api, CurrencyPair $pair, OutputInterface $output)
    {
        $json = $this->initializeJSON($pair);

        /** @var Candle $lastCandle */
        $lastCandle = $this->entityManager
            ->getRepository(Candle::class)
            ->findLast($pair);

        if(!$lastCandle) {
            $lastTime = time() - 10368000; //100 days
        } else {
            $lastTime = $lastCandle->getCloseTime();
        }

        $totalCandles = 0;

        $candles = $api->getCandles($pair, TimeFrames::TIMEFRAME_5M, $lastTime);

        /** @var Candle $candle */
        foreach($candles as $candle) {
            if($candle->getCloseTime() < time()) {
                //$this->entityManager->persist($candle);
                fwrite($json, json_encode($candle).",");
                $totalCandles ++;
            }
        }
        //$this->entityManager->flush();
        $output->writeln([
            get_class($api),
            $pair->getSymbol(),
            "new candles: $totalCandles",
        ]);
        $this->closeJSON($json);
    }

    /**
     * @param CurrencyPair $pair
     * @return bool|resource
     */
    private function initializeJSON(CurrencyPair $pair)
    {
        $json = fopen($this->parameterBag->get('kernel.project_dir').'/public/charts/chart_'.$pair->getId().".json", 'a');

        $stat = fstat($json);
        $size = $stat['size'];

        if($size == 0) {
            fwrite($json, "[");
        } else {
            ftruncate($json, $stat['size']-1);
        }
        return $json;
    }

    /**
     * @param resource $json
     */
    private function closeJSON($json)
    {
        fwrite($json, "]");
        fclose($json);
    }
}
