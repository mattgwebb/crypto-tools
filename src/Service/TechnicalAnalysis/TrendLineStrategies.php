<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\StrategyResult;
use App\Entity\Data\Candle;
use App\Entity\TechnicalAnalysis\PivotPoint;
use App\Entity\TechnicalAnalysis\PivotTypes;
use App\Entity\TechnicalAnalysis\TrendLine;

class TrendLineStrategies extends AbstractStrategyService
{
    /**
     * @param array $candles
     * @return StrategyResult
     */
    public function supportAndResistance(array $candles)
    {
        $trendLines = $this->detectTrendLines($candles);
        //$linesMetResult = $this->supportAndResistanceLinesMet($trendLines);
        //$breakoutsResult = $this->supportAndResistanceLinesBreakouts($trendLines);
        return new StrategyResult();
    }

    /**
     * @param array $candles
     * @return array
     */
    public function detectTrendLines(array $candles)
    {
        $trendLines = [];
        $pivotPoints = $this->getPivotPoints($candles);
        $lines = $this->getLinePoints($pivotPoints);

        foreach($lines as $pivotTouches) {
            if(count($pivotTouches) < 3) {
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

            if($trendLine->getType() == TrendLine::TYPE_SUPPORT) {
                if($currentCandle->getClosePrice() <= $linePrice && $previousCandle->getClosePrice() > $linePrice) {
                    $result->setTradeResult(StrategyResult::TRADE_LONG);
                    return $result;
                }
            } else if($trendLine->getType() == TrendLine::TYPE_RESISTANCE) {
                if($currentCandle->getClosePrice() >= $linePrice && $previousCandle->getClosePrice() < $linePrice) {
                    $result->setTradeResult(StrategyResult::TRADE_SHORT);
                    return $result;
                }
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
}