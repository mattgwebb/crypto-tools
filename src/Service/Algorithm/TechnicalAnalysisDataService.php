<?php


namespace App\Service\Algorithm;


use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\TimeFrames;
use App\Entity\TechnicalAnalysis\IndicatorTypes;
use App\Entity\TechnicalAnalysis\IndicatorValue;
use App\Entity\TechnicalAnalysis\TrendLine;
use App\Repository\Data\CurrencyPairRepository;
use App\Repository\TechnicalAnalysis\IndicatorValueRepository;
use App\Repository\TechnicalAnalysis\TrendLineRepository;
use App\Service\TechnicalAnalysis\Indicators;
use App\Service\TechnicalAnalysis\TrendLineStrategies;
use Doctrine\ORM\EntityManagerInterface;


class TechnicalAnalysisDataService
{

    /**
     * @var CurrencyPairRepository
     */
    private $currencyPairRepo;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TrendLineStrategies
     */
    private $trendLineStrategies;

    /**
     * @var TrendLineRepository
     */
    private $trendLineRepository;

    /**
     * @var IndicatorValueRepository
     */
    private $indicatorValueRepository;

    /**
     * @var Indicators
     */
    private $indicatorsService;

    /**
     * TechnicalAnalysisDataService constructor.
     * @param CurrencyPairRepository $currencyPairRepo
     * @param EntityManagerInterface $entityManager
     * @param TrendLineStrategies $trendLineStrategies
     * @param TrendLineRepository $trendLineRepository
     * @param IndicatorValueRepository $indicatorValueRepository
     * @param Indicators $indicatorsService
     */
    public function __construct(CurrencyPairRepository $currencyPairRepo, EntityManagerInterface $entityManager,
                                TrendLineStrategies $trendLineStrategies, TrendLineRepository $trendLineRepository,
                                IndicatorValueRepository $indicatorValueRepository, Indicators $indicatorsService)
    {
        $this->currencyPairRepo = $currencyPairRepo;
        $this->entityManager = $entityManager;
        $this->trendLineStrategies = $trendLineStrategies;
        $this->trendLineRepository = $trendLineRepository;
        $this->indicatorValueRepository = $indicatorValueRepository;
        $this->indicatorsService = $indicatorsService;
    }

    /**
     * @param CurrencyPair $pair
     */
    public function loadNewData(CurrencyPair $pair)
    {
        // TODO check if a new daily candle has closed before loading candles

        $allCandles = $this->currencyPairRepo->getCandlesByTimeFrame($pair, TimeFrames::TIMEFRAME_1D);

        $this->loadNewTrendLines($pair, $allCandles);

        $this->loadNewIndicatorValues($pair, $allCandles);
    }

    private function loadNewTrendLines(CurrencyPair $pair, array $candles, int $numberCandlesToCheck = 365, int $minPivotTouches = 4)
    {
        /** @var TrendLine $latestTrendLine */
        $latestTrendLine = $this->trendLineRepository->findOneBy(["currencyPair" => $pair], ["createdAt" => "desc"]);
        $latestTimestamp = $latestTrendLine ? $latestTrendLine->getCreatedAt() : false;

        $lastCandleIndex = count($candles) - 1;

        for($i = $lastCandleIndex; $i >= 20; $i--) {

            /** @var Candle $candle */
            $candle = $candles[$i];

            if($latestTimestamp && $candle->getCloseTime() <= $latestTimestamp) {
                break;
            }

            $startingPoint = $i - $numberCandlesToCheck + 1;
            $length = $numberCandlesToCheck;

            if($startingPoint < 0) {
                $length += $startingPoint;
                $startingPoint = 0;
            }

            $currentCandles = array_slice($candles, $startingPoint, $length);

            $newTrendLines = $this->trendLineStrategies->detectTrendLines($currentCandles, $minPivotTouches);

            /** @var TrendLine $trendLine */
            foreach($newTrendLines as $trendLine) {
                $trendLine->setTimeFrame(TimeFrames::TIMEFRAME_1D);
                $trendLine->setCurrencyPair($pair);
                $trendLine->setCreatedAt($candle->getCloseTime());
                $this->entityManager->persist($trendLine);
            }
            $this->entityManager->flush();
        }
    }

    /**
     * @param CurrencyPair $pair
     * @param array $candles
     */
    private function loadNewIndicatorValues(CurrencyPair $pair, array $candles)
    {
        /** @var IndicatorValue $latestValue */
        $latestValue = $this->indicatorValueRepository->findOneBy(["currencyPair" => $pair], ["createdAt" => "desc"]);
        $latestTimestamp = $latestValue ? $latestValue->getCreatedAt() : false;

        $data = $this->indicatorsService->prepareDataFromCandles($candles);

        $adxPeriod = $this->indicatorsService->adxPeriod($data);
        $adxPeriod = array_reverse($adxPeriod, true);

        foreach($adxPeriod as $period => $value) {
            $close = $data['close_time'][$period];

            if($latestTimestamp && $close <= $latestTimestamp) {
                break;
            }

            $indicatorValue = new IndicatorValue();
            $indicatorValue->setValue($value);
            $indicatorValue->setTimeFrame(TimeFrames::TIMEFRAME_1D);
            $indicatorValue->setCurrencyPair($pair);
            $indicatorValue->setCreatedAt($close);
            $indicatorValue->setIndicator(IndicatorTypes::ADX);
            $this->entityManager->persist($indicatorValue);
        }
        $this->entityManager->flush();
    }
}