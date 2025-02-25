<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\StrategyResult;
use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\TimeFrames;
use App\Entity\TechnicalAnalysis\PivotPoint;
use App\Entity\TechnicalAnalysis\PivotTypes;
use App\Entity\TechnicalAnalysis\TrendLine;
use App\Repository\TechnicalAnalysis\TrendLineRepository;
use Doctrine\ORM\EntityManagerInterface;

class TrendLineStrategies extends AbstractStrategyService
{

    /**
     * @var TrendLineRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var array
     */
    private $trendLines;

    /**
     * DivergenceStrategies constructor.
     * @param Indicators $indicators
     * @param TrendLineRepository $repository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Indicators $indicators, TrendLineRepository $repository, EntityManagerInterface $entityManager)
    {
        parent::__construct($indicators);
        $this->repository = $repository;
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $candles
     * @return StrategyResult
     */
    public function supportAndResistance(array $candles)
    {
        $currentCandle = $candles[count($candles) - 1];
        $pair = $currentCandle->getCurrencyPair();

        $trendLines = $this->getSavedTrendLines($pair, $currentCandle->getCloseTime());

        if(!$trendLines) {
            return new StrategyResult();
        }

        $linesMetResult = $this->supportAndResistanceLinesMet($trendLines, $candles);
        //$breakoutsResult = $this->supportAndResistanceLinesBreakouts($trendLines, $candles);
        return $linesMetResult;
    }

    /**
     * @param array $candles
     * @param int $minPivotTouches
     * @return array
     */
    public function detectTrendLines(array $candles, int $minPivotTouches = 3)
    {
        $trendLines = [];
        $pivotPoints = $this->getPivotPoints($candles);
        $lines = $this->getLinePoints($pivotPoints);

        foreach($lines as $pivotTouches) {
            if(count($pivotTouches) < $minPivotTouches) {
                continue;
            }
            $lineTouches = array_values($pivotTouches);

            $trendLines[] = $this->newTrendLine($lineTouches);
        }
        $trendLines = $this->removeSimilarTrendLines($trendLines);
        return $trendLines;
    }

    /**
     * @param array $trendLines
     * @return array
     */
    private function removeSimilarTrendLines(array $trendLines)
    {
        $trendLinesByPrice = [];

        /** @var TrendLine $trendLine */
        foreach($trendLines as $key => $trendLine) {
            $newPriceRange = true;
            foreach($trendLinesByPrice as $price => $mergedTrendLine) {
                if((abs($trendLine->getStartPrice() - $price) / $price) < 0.015) {
                    $trendLinesByPrice[$price]->setStartTime(min([$trendLine->getStartTime(), $mergedTrendLine->getStartTime()]));
                    $trendLinesByPrice[$price]->setEndTime(max([$trendLine->getEndTime(), $mergedTrendLine->getEndTime()]));
                    $newPriceRange = false;
                    break;
                }
            }
            if($newPriceRange) {
                $trendLinesByPrice[$trendLine->getStartPrice()] = $trendLine;
            }
        }
        return array_values($trendLinesByPrice);
    }

    /**
     * @param array $lineTouches
     * @return TrendLine
     */
    private function newTrendLine(array $lineTouches)
    {
        $price = $lineTouches[0]->getCandle()->getClosePrice();

        usort($lineTouches, function(PivotPoint $a, PivotPoint $b)
        { return $a->getCandle()->getCloseTime() > $b->getCandle()->getCloseTime(); });

        $tops = $bottoms = 0;
        /** @var PivotPoint $pivotPoint */
        foreach($lineTouches as $pivotPoint) {
            if($pivotPoint->getType() == PivotTypes::TOP) {
                $tops ++;
            } else if($pivotPoint->getType() == PivotTypes::BOTTOM) {
                $bottoms ++;
            }
        }

        $firstTouch = $lineTouches[0];
        $lastTouch = $lineTouches[count($lineTouches) - 1];

        $trendLine = new TrendLine();
        $trendLine->setStartPrice($price);
        $trendLine->setEndPrice($price);
        $trendLine->setStartTime($firstTouch->getCandle()->getCloseTime());
        $trendLine->setEndTime($lastTouch->getCandle()->getCloseTime());

        if($tops > $bottoms) {
            $trendLine->setType(TrendLine::TYPE_RESISTANCE);
        } else {
            $trendLine->setType(TrendLine::TYPE_SUPPORT);
        }

        return $trendLine;
    }

    /**
     * @param array $candles
     * @return array
     */
    private function getPivotPoints(array $candles)
    {
        $pivotPoints = [];

        foreach($candles as $key => $candle) {
            if(isset($candles[$key - 2]) && isset($candles[$key + 2])) {

                $previousClose = $candles[$key - 1]->getClosePrice();
                $nextClose = $candles[$key + 1]->getClosePrice();
                $previousPreviousClose = $candles[$key - 2]->getClosePrice();
                $nextNextClose = $candles[$key + 2]->getClosePrice();

                if($candle->getClosePrice() < $previousClose && $candle->getClosePrice() < $nextClose &&
                    $candle->getClosePrice() < $previousPreviousClose && $candle->getClosePrice() < $nextNextClose) {
                    $pivotPoints[] = new PivotPoint($candle, PivotTypes::BOTTOM);
                }

                if($candle->getClosePrice() > $previousClose && $candle->getClosePrice() > $nextClose &&
                    $candle->getClosePrice() > $previousPreviousClose && $candle->getClosePrice() > $nextNextClose) {
                    $pivotPoints[] = new PivotPoint($candle, PivotTypes::TOP);
                }
            }
        }
        return $pivotPoints;
    }

    /**
     * @param array $pivotPoints
     * @return array
     */
    private function getLinePoints(array $pivotPoints)
    {
        $pivotTouches = [];
        /** @var PivotPoint $pivotPoint */
        foreach($pivotPoints as $pivotPoint) {
            $pivotPointCandle = $pivotPoint->getCandle();
            /**
             * @var int $key
             * @var  PivotPoint $potentialTouchPoint
             */
            foreach($pivotPoints as $key => $potentialTouchPoint) {
                $potentialTouchCandle = $potentialTouchPoint->getCandle();
                if($pivotPointCandle->getOpenTime() == $potentialTouchCandle->getOpenTime()) {
                    continue;
                }
                if($pivotPointCandle->isTouchingCandle($potentialTouchCandle)) {
                    if(!isset($pivotTouches[$pivotPointCandle->getOpenTime()])) {
                        $pivotTouches[$pivotPointCandle->getOpenTime()][] = $pivotPoint;
                    }
                    $pivotTouches[$pivotPointCandle->getOpenTime()][] = $potentialTouchPoint;
                }
            }
        }

        usort($pivotTouches, function($a, $b)
        { return(count($a) < count($b)); });

        $usedCandles = [];
        foreach($pivotTouches as $key => $pivotTouch) {
            foreach($pivotTouch as $touchKey => $pivotPoint) {
                $candle = $pivotPoint->getCandle();
                if(!in_array($candle->getOpenTime(), $usedCandles)) {
                    $usedCandles[] = $candle->getOpenTime();
                } else {
                    unset($pivotTouches[$key][$touchKey]);
                }
            }
        }
        return $pivotTouches;
    }

    /**
     * @param array $trendLines
     * @param array $candles
     * @return StrategyResult
     */
    private function supportAndResistanceLinesMet(array $trendLines, array $candles)
    {
        $totalCandles = count($candles);

        /** @var Candle $currentCandle */
        $currentCandle = $candles[$totalCandles - 1];
        /** @var Candle $previousCandle */
        $previousCandle = $candles[$totalCandles - 2];

        $result = new StrategyResult();

        /** @var TrendLine $trendLine */
        foreach($trendLines as $trendLine) {
            if(!$this->checkTrendLineTime($trendLine, $currentCandle)) {
                //continue;
            }
            $linePrice = $this->getTrendLinePrice($trendLine, $currentCandle);

            $result->setExtraData(['trend_line' => $trendLine]);

            if(($currentCandle->getClosePrice() <= $linePrice) && $previousCandle->getClosePrice() > $linePrice) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
                return $result;
            } else if(($currentCandle->getClosePrice() >= $linePrice) && $previousCandle->getClosePrice() < $linePrice) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
                return $result;
            }
        }
        return $result;
    }

    /**
     * @param array $trendLines
     * @param array $candles
     * @return StrategyResult
     */
    private function supportAndResistanceLinesBreakouts(array $trendLines, array $candles)
    {
        $totalCandles = count($candles);

        /** @var Candle $currentCandle */
        $currentCandle = $candles[$totalCandles - 1];

        $result = new StrategyResult();
        foreach($trendLines as $trendLine) {
            if(!$this->checkTrendLineTime($trendLine, $currentCandle)) {
                continue;
            }
            $price = $this->getTrendLinePrice($trendLine, $currentCandle);

            if($trendLine->getType() == TrendLine::TYPE_SUPPORT) {
                if($currentCandle->getClosePrice() <= ($price * 0.97)) {
                    $result->setTradeResult(StrategyResult::TRADE_SHORT);
                }
            } else if($trendLine->getType() == TrendLine::TYPE_RESISTANCE) {
                if($currentCandle->getClosePrice() >= ($price * 1.03)) {
                    $result->setTradeResult(StrategyResult::TRADE_LONG);
                }
            }
        }
        return $result;
    }

    /**
     * @param TrendLine $trendLine
     * @param Candle $currentCandle
     * @return float
     */
    private function getTrendLinePrice(TrendLine $trendLine, Candle $currentCandle)
    {
        if($trendLine->getStartPrice() == $trendLine->getEndPrice()) {
            return $trendLine->getStartPrice();
        }
        $timeRange = $trendLine->getEndTime() - $trendLine->getStartTime();
        $priceRange = $trendLine->getEndPrice() - $trendLine->getStartPrice();
        $timeDifference = $currentCandle->getCloseTime() - $trendLine->getStartTime();

        return $trendLine->getStartPrice() + (($timeDifference / $timeRange) * $priceRange);
    }

    /**
     * @param TrendLine $trendLine
     * @param Candle $currentCandle
     * @return bool
     */
    private function checkTrendLineTime(TrendLine $trendLine, Candle $currentCandle)
    {
        return $currentCandle->getCloseTime() >= $trendLine->getStartTime() &&
            $currentCandle->getCloseTime() <= $trendLine->getEndTime();
    }

    /**
     * @param CurrencyPair $pair
     * @param int $lastClose
     * @return TrendLine[]
     */
    private function getSavedTrendLines(CurrencyPair $pair, int $lastClose)
    {
        $lastDailyClose = $this->getLastClose($lastClose, TimeFrames::TIMEFRAME_1D);

        if(!isset($this->trendLines[$lastDailyClose])) {
            $trendLines = $this->repository->findBy(['currencyPair' => $pair, 'createdAt' => $lastDailyClose]);

            if(!$trendLines) {
                $this->trendLines[$lastDailyClose] = [];
            } else {
                /** @var TrendLine $trendLine */
                foreach($trendLines as $trendLine) {
                    $this->trendLines[$lastDailyClose][] = $trendLine;
                }
            }
        }
        return $this->trendLines[$lastDailyClose];
    }

    /**
     * @param int $timestamp
     * @param int $timeFrame
     * @return int
     */
    private function getLastClose(int $timestamp, int $timeFrame)
    {
        $timeFrameSeconds = $timeFrame * 60;

        $difference = ($timestamp + 1) % $timeFrameSeconds;

        return $difference == 0 ? $timestamp : $timestamp - $difference;
    }
}