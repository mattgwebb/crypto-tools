<?php


namespace App\Service\Algorithm;


use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\TimeFrames;
use App\Entity\TechnicalAnalysis\TrendLine;
use App\Repository\Data\CurrencyPairRepository;
use App\Repository\TechnicalAnalysis\TrendLineRepository;
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
     * TechnicalAnalysisDataService constructor.
     * @param CurrencyPairRepository $currencyPairRepo
     * @param EntityManagerInterface $entityManager
     * @param TrendLineStrategies $trendLineStrategies
     * @param TrendLineRepository $trendLineRepository
     */
    public function __construct(CurrencyPairRepository $currencyPairRepo, EntityManagerInterface $entityManager, TrendLineStrategies $trendLineStrategies, TrendLineRepository $trendLineRepository)
    {
        $this->currencyPairRepo = $currencyPairRepo;
        $this->entityManager = $entityManager;
        $this->trendLineStrategies = $trendLineStrategies;
        $this->trendLineRepository = $trendLineRepository;
    }

    /**
     * @param CurrencyPair $pair
     */
    public function loadNewData(CurrencyPair $pair)
    {
        // TODO check if a new daily candle has closed before loading candles

        $allCandles = $this->currencyPairRepo->getCandlesByTimeFrame($pair, TimeFrames::TIMEFRAME_1D);

        $this->loadNewTrendLines($pair, $allCandles);
    }

    private function loadNewTrendLines(CurrencyPair $pair, array $candles)
    {
        /** @var TrendLine $latestTrendLine */
        $latestTrendLine = $this->trendLineRepository->findOneBy(["currencyPair" => $pair], ["createdAt" => "desc"]);
        $latestTimestamp = $latestTrendLine ? $latestTrendLine->getCreatedAt() : false;

        $lastCandleIndex = count($candles) - 1;

        for($i = $lastCandleIndex; $i >= 20; $i--) {

            /** @var Candle $candle */
            $candle = $candles[$i];

            if($latestTimestamp && $candle->getCloseTime() < $latestTimestamp) {
                break;
            }

            $currentCandles = array_slice($candles, 0, $i);

            $newTrendLines = $this->trendLineStrategies->detectTrendLines($currentCandles);

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


}