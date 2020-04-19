<?php


namespace App\Service\Data;


use App\Entity\Data\Candle;
use App\Entity\Data\Currency;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\Exchange;
use App\Entity\Data\ExternalIndicatorData;
use App\Entity\Data\ExternalIndicatorDataType;
use App\Entity\Data\TimeFrames;
use App\Service\Exchanges\ApiFactory;
use App\Service\Exchanges\ApiInterface;
use App\Service\ThirdPartyAPIs\GreedAndFearIndexAPI;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExternalDataService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * ExternalDataService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
    }

    /**
     *
     */
    public function loadAllBalances()
    {
        $exchanges = $this->getAllExchanges();

        foreach($exchanges as $exchange) {
            $api = ApiFactory::getApi($exchange);
            $rawBalances = $api->getUserBalance();

            $currencies = $exchange->getCurrencies();

            /** @var Currency $currency */
            foreach($currencies as $currency) {
                if(isset($rawBalances[$currency->getSymbol()])) {
                    $currency->setBalance($rawBalances[$currency->getSymbol()]);
                    $this->entityManager->persist($currency);
                }
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @param Currency $currency
     * @return float
     */
    public function loadBalance(Currency $currency)
    {
        $balance = 0.00;
        $api = ApiFactory::getApi($currency->getExchange());

        $rawBalances = $api->getUserBalance();
        if(isset($rawBalances[$currency->getSymbol()])) {
            $balance = $rawBalances[$currency->getSymbol()];
        }
        return $balance;
    }

    /**
     * @return array
     */
    public function loadAllNewCandles()
    {
        $updatedPairs = [];

        $exchanges = $this->getAllExchanges();

        /** @var Exchange $exchange */
        foreach($exchanges as $exchange) {
            $api = ApiFactory::getApi($exchange);
            $currencies = $exchange->getCurrencies();

            /** @var Currency $currency */
            foreach($currencies as $currency) {
                /** @var CurrencyPair $pair */
                foreach ($currency->getPairs() as $pair) {
                    list($newCandles, $lastCandle, $lastPrice) = $this->loadNewCandles($api, $pair);
                    $updatedPairs[$pair->getSymbol()] = $newCandles;
                }
            }
        }
        return $updatedPairs;
    }

    /**
     * @param CurrencyPair $pair
     * @return array
     */
    public function loadPairNewCandles(CurrencyPair $pair)
    {
        /** @var Exchange $exchange */
        $exchange = $pair->getFirstCurrency()->getExchange();
        $api = ApiFactory::getApi($exchange);
        return $this->loadNewCandles($api, $pair);
    }

    /**
     * @return array
     */
    public function loadAllExternalIndicatorData()
    {
        $newData = [];

        $indicatorTypes = $this->entityManager
            ->getRepository(ExternalIndicatorDataType::class)
            ->findAll();

        /** @var ExternalIndicatorDataType $indicatorDataType */
        foreach($indicatorTypes as $indicatorDataType) {
            $newData[$indicatorDataType->getName()] = $this->loadExternalIndicatorData($indicatorDataType);
        }
        return $newData;
    }

    /**
     * @param ExternalIndicatorDataType $indicatorDataType
     * @return int
     */
    private function loadExternalIndicatorData(ExternalIndicatorDataType $indicatorDataType)
    {
        $newDataLoaded = 0;

        if($indicatorDataType->getName() == 'fear_greed_index') {
            $api = new GreedAndFearIndexAPI();
            $data = $api->getData();

            /** @var ExternalIndicatorData $latestData */
            $latestData =  $this->entityManager
                ->getRepository(ExternalIndicatorData::class)
                ->getLatestData($indicatorDataType);

            foreach($data as $dailyValue) {
                if(!$latestData || $dailyValue['timestamp'] > $latestData->getCloseTime()) {
                    $close = (int)$dailyValue['timestamp'];
                    $value = (float)$dailyValue['value'];

                    $newData = new ExternalIndicatorData($close, $value, $indicatorDataType);
                    $this->entityManager->persist($newData);
                    $newDataLoaded ++;
                }
            }
            $this->entityManager->flush();
        }
        return $newDataLoaded;
    }

    /**
     * @param ApiInterface $api
     * @param CurrencyPair $pair
     * @return array
     */
    private function loadNewCandles(ApiInterface $api, CurrencyPair $pair)
    {
        //$json = $this->initializeJSON($pair);

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

        $lastCandle = new Candle();

        /** @var Candle $candle */
        foreach($candles as $candle) {
            if($candle->getCloseTime() < time()) {
                $this->entityManager->persist($candle);
                //fwrite($json, json_encode($candle).",");
                $totalCandles ++;
                $lastCandle = $candle;
            }
        }
        $this->entityManager->flush();
        //$this->closeJSON($json);

        return [$totalCandles, $lastCandle, $candle->getClosePrice()];
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

    /**
     * @return Exchange[]
     */
    private function getAllExchanges()
    {
        /** @var Exchange[] $exchanges */
        $exchanges = $this->entityManager
            ->getRepository(Exchange::class)
            ->findAll();
        return $exchanges;
    }
}