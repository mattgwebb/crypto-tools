<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\StrategyResult;
use App\Entity\TechnicalAnalysis\DivergenceLine;
use App\Entity\TechnicalAnalysis\DivergenceTypes;
use App\Entity\TechnicalAnalysis\IndicatorPointList;
use App\Entity\TechnicalAnalysis\IndicatorTypes;

class DivergenceStrategies extends AbstractStrategyService
{

    /**
     * @param array $data
     * @param string $indicator
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @param bool $regularDivergence
     * @param bool $hiddenDivergence
     * @return StrategyResult
     */
    public function indicatorDivergence(array $data, string $indicator, int $previousCandles, int $minCandleDifference, int $minDivergencePercentage,
                                         bool $regularDivergence, bool $hiddenDivergence): StrategyResult
    {
        $divergenceTypes = [];

        if($regularDivergence) {
            $divergenceTypes[] = DivergenceTypes::BULLISH_REGULAR_DIVERGENCE;
            $divergenceTypes[] = DivergenceTypes::BEARISH_REGULAR_DIVERGENCE;
        }

        if($hiddenDivergence) {
            $divergenceTypes[] = DivergenceTypes::BULLISH_HIDDEN_DIVERGENCE;
            $divergenceTypes[] = DivergenceTypes::BEARISH_HIDDEN_DIVERGENCE;
        }

        $result = new StrategyResult();

        if($indicator == IndicatorTypes::RSI) {
            $indicatorPeriod = $this->indicators->rsiPeriod($data);
        } else if($indicator == IndicatorTypes::OBV) {
            $indicatorPeriod = $this->indicators->obvPeriod($data);
        } else if($indicator == IndicatorTypes::CHAIKIN) {
            $indicatorPeriod = $this->indicators->chaikinOscillatorPeriod($data);
        } else if($indicator == IndicatorTypes::MFI) {
            $indicatorPeriod = $this->indicators->mfiPeriod($data);
        } else {
            return $result;
        }

        $indicatorPeriod = array_values($indicatorPeriod);
        $indicatorPeriod = array_slice($indicatorPeriod, $previousCandles * (-1));
        $indicatorPeriod = array_reverse($indicatorPeriod);

        $lastOpenTimes = array_slice($data['open_time'], $previousCandles * (-1));
        $lastOpenTimes = array_reverse($lastOpenTimes);

        $lastCloses = array_slice($data['close'], $previousCandles * (-1));
        $lastCloses = array_reverse($lastCloses);

        $priceRange = $this->getRange($lastCloses);
        $indicatorRange = $this->getRange($indicatorPeriod);

        $indicatorPoints = new IndicatorPointList($indicatorPeriod, $lastOpenTimes, $lastCloses);

        $orderedIndicatorPointsAsc = $indicatorPoints->getOrderedList();

        $divergenceLines = [];

        foreach($orderedIndicatorPointsAsc as $lowPoint) {
            if($lowPoint->getPeriod() >= $minCandleDifference) {
                $line = $indicatorPoints->getValidLine($lowPoint->getPeriod(), true);
                if($line) {
                    $this->checkDivergence($line, $lastCloses, $minDivergencePercentage, $priceRange, $indicatorRange, true);
                    if(in_array($line->getType(), $divergenceTypes)) {
                        $divergenceLines[] = $line;
                    }
                }
            }
        }

        $orderedIndicatorPointsDesc = $indicatorPoints->getOrderedList(true);

        foreach($orderedIndicatorPointsDesc as $highPoint) {
            if($highPoint->getPeriod() >= $minCandleDifference) {
                $line = $indicatorPoints->getValidLine($highPoint->getPeriod(), false);
                if($line) {
                    $this->checkDivergence($line, $lastCloses, $minDivergencePercentage, $priceRange, $indicatorRange,false);
                    if(in_array($line->getType(), $divergenceTypes)) {
                        $divergenceLines[] = $line;
                    }
                }
            }
        }
        $finalLine = [];

        if($divergenceLines) {
            if(count($divergenceLines) > 1) {
                usort($divergenceLines, function(DivergenceLine $a, DivergenceLine $b)
                { return($a->getPercentageDivergenceWithPrice() < $b->getPercentageDivergenceWithPrice()); });
            }
            /** @var DivergenceLine $finalLine */
            $finalLine = $divergenceLines[0];

            if($finalLine->hasBullishDivergence()) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($finalLine->hasBearishDivergence()) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        $result->setExtraData(['divergence_line' => $finalLine]);

        return $result;
    }

    /**
     * @param array $values
     * @return float
     */
    private function getRange(array $values)
    {
        $smallest = min($values);
        $biggest = max($values);

        return $biggest - $smallest;
    }

    /**
     * @param DivergenceLine $line
     * @param array $lastCloses
     * @param int $minDivergencePercentage
     * @param float $priceRange
     * @param float $indicatorRange
     * @param bool $lower
     */
    private function checkDivergence(DivergenceLine $line, array $lastCloses, int $minDivergencePercentage,
                                     float $priceRange, float $indicatorRange, bool $lower)
    {
        $firstPeriod = $line->getFirstPoint()->getPeriod();
        $secondPeriod = $line->getSecondPoint()->getPeriod();

        $firstPeriodClose = $lastCloses[$firstPeriod];
        $secondPeriodClose = $lastCloses[$secondPeriod];

        $priceClosePercentageChange = (($secondPeriodClose - $firstPeriodClose) / $priceRange) * 100;
        $indicatorPercentageChange = (($line->getSecondPoint()->getValue() - $line->getFirstPoint()->getValue()) / $indicatorRange) * 100;

        $indicatorUpPriceDown = $indicatorPercentageChange < 0 && $priceClosePercentageChange > 0;
        $indicatorDownPriceUp = $indicatorPercentageChange > 0 && $priceClosePercentageChange < 0;

        $line->setPercentageDivergenceWithPrice(abs($priceClosePercentageChange - $indicatorPercentageChange));
        if($line->getPercentageDivergenceWithPrice() >= $minDivergencePercentage) {
            if($lower) {
                if($indicatorDownPriceUp) {
                    $line->setType(DivergenceTypes::BULLISH_HIDDEN_DIVERGENCE);
                } else if($indicatorUpPriceDown) {
                    $line->setType(DivergenceTypes::BULLISH_REGULAR_DIVERGENCE);
                }
            } else {
                if($indicatorDownPriceUp) {
                    $line->setType(DivergenceTypes::BEARISH_REGULAR_DIVERGENCE);
                } else if($indicatorUpPriceDown) {
                    $line->setType(DivergenceTypes::BEARISH_HIDDEN_DIVERGENCE);
                }
            }
        }
    }
}