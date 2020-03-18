<?php


namespace App\Service;


use App\Entity\BotAlgorithm;
use App\Entity\Candle;
use App\Entity\DivergenceLine;
use App\Entity\DivergenceTypes;
use App\Entity\IndicatorPoint;
use App\Entity\IndicatorPointList;
use App\Entity\StrategyResult;
use App\Entity\StrategyTypes;
use App\Entity\TrendLine;

class Strategies
{

    const STRATEGY_LIST = [
        StrategyTypes::RSI_BOLLINGER,
        StrategyTypes::RSI_MACD,
        StrategyTypes::SUPPORT_RESISTANCE,
        StrategyTypes::RSI_DIVERGENCE,
        StrategyTypes::OBI_DIVERGENCE,
        StrategyTypes::EMA_SCALP,
        StrategyTypes::EMA_CROSSOVER
    ];

    /**
     * Minumum candle difference for line divergences (so we don´t draw lines between two adjacent points)
     */
    const MIN_CANDLE_DIFFERENCE_DIVERGENCE = 2;

    /**
     * @var Indicators
     */
    private $indicators;

    /**
     * @var array
     */
    private $data;

    /**
     * @var float
     */
    private $currentPrice;

    /**
     * @var int
     */
    private $currentClose;

    /**
     * Strategies constructor.
     * @param Indicators $indicators
     */
    public function __construct(Indicators $indicators)
    {
        $this->indicators = $indicators;
    }

    /**
     * @param float $currentPrice
     */
    public function setCurrentPrice(float $currentPrice): void
    {
        $this->currentPrice = $currentPrice;
    }



    public function rsiAndBollinger(float $rsiSell = 70.00, float $rsiBuy = 30.00) : StrategyResult
    {
        list($highBand, $lowBand) = $this->indicators->bollingerBands($this->data);

        if($this->currentPrice < $lowBand) {
            $bollingerResult = StrategyResult::TRADE_LONG;
        } else if($this->currentPrice > $highBand) {
            $bollingerResult = StrategyResult::TRADE_SHORT;
        } else {
            $bollingerResult = StrategyResult::NO_TRADE;
        }

        $rsi = $this->indicators->rsi($this->data);

        if($rsi < $rsiBuy) {
            $rsiResult = StrategyResult::TRADE_LONG;
        } else if($rsi > $rsiSell) {
            $rsiResult = StrategyResult::TRADE_SHORT;
        } else {
            $rsiResult = StrategyResult::NO_TRADE;
        }

        $result = new StrategyResult();

        if($rsiResult == StrategyResult::TRADE_LONG && $bollingerResult == StrategyResult::TRADE_LONG) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($rsiResult == StrategyResult::TRADE_SHORT && $bollingerResult == StrategyResult::TRADE_SHORT) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }

        return $result;
    }

    public function rsiAndMacd(float $rsiSell = 70.00, float $rsiBuy = 30.00) : StrategyResult
    {
        $macd = $this->indicators->macd($this->data);
        $rsi = $this->indicators->rsi($this->data);

        $result = new StrategyResult();

        if($rsi < $rsiBuy && $macd > 0) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($rsi > $rsiSell && $macd < 0) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }

        return $result;
    }

    /**
     * @return StrategyResult
     */
    public function emaScalp() : StrategyResult
    {
        $result = new StrategyResult();
        $red = $redp = $green = $greenp = [];

        $e1 = [2,3,4,5,6,7,8,9,10,11,12,13,14,15];      // red
        $e3 = [44,47,50,53,56,59,62,65,68,71,74];       // green
        foreach ($e1 as $e) {
            $red[] = $this->indicators->ema($this->data['close'], $e);
            $redp[] = $this->indicators->ema($this->data['close'], $e, 1); // prior
        }
        $red_avg = (array_sum($red)/count($red));
        $redp_avg = (array_sum($redp)/count($redp));


        foreach ($e3 as $e) {
            $green[] = $this->indicators->ema($this->data['close'], $e);
        }
        $green_avg = (array_sum($green)/count($green));

        if ($red_avg < $green_avg && $redp_avg > $green_avg){
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        }
        if ($red_avg > $green_avg && $redp_avg < $green_avg){
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param int $period1
     * @param int $period2
     * @return StrategyResult
     */
    public function emaCrossover($period1 = 10, $period2 = 20) : StrategyResult
    {
        $period1EMA = $this->indicators->ema($this->data['close'], $period1);
        $period1PriorEMA = $this->indicators->ema($this->data['close'], $period1, 1); // prior

        $period2EMA = $this->indicators->ema($this->data['close'], $period2);
        $period2PriorEMA = $this->indicators->ema($this->data['close'], $period2, 1); // prior

        return $this->checkCrossMovingAverages($period1EMA, $period1PriorEMA, $period2EMA, $period2PriorEMA);
    }

    /**
     * @param int $period1
     * @param int $period2
     * @return StrategyResult
     */
    public function goldenCross($period1 = 50, $period2 = 200) : StrategyResult
    {
        $period1MA = $this->indicators->ma($this->data['close'], $period1);
        $period1PriorMA = $this->indicators->ma($this->data['close'], $period1, 1); // prior

        $period2MA = $this->indicators->ma($this->data['close'], $period2);
        $period2PriorMA = $this->indicators->ma($this->data['close'], $period2, 1); // prior

        return $this->checkCrossMovingAverages($period1MA, $period1PriorMA, $period2MA, $period2PriorMA);
    }

    public function rsiDivergence(int $previousCandles = 10): StrategyResult
    {
        $result = new StrategyResult();

        $rsiPeriod = $this->indicators->rsiPeriod($this->data);

        $rsiPeriod = array_values($rsiPeriod);
        $rsiPeriod = array_slice($rsiPeriod, $previousCandles * (-1));
        $rsiPeriod = array_reverse($rsiPeriod);

        $lastOpenTimes = array_slice($this->data['open_time'], $previousCandles * (-1));
        $lastOpenTimes = array_reverse($lastOpenTimes);

        $lastCloses = array_slice($this->data['close'], $previousCandles * (-1));
        $lastCloses = array_reverse($lastCloses);

        $rsiPoints = new IndicatorPointList($rsiPeriod, $lastOpenTimes, $lastCloses);

        $orderedRSIPointsAsc = $rsiPoints->getOrderedList();

        $divergenceLines = [];

        foreach($orderedRSIPointsAsc as $lowPoint) {
            if($lowPoint->getPeriod() >= self::MIN_CANDLE_DIFFERENCE_DIVERGENCE) {
                $line = $rsiPoints->getValidLine($lowPoint->getPeriod(), true);
                if($line) {
                    $this->checkDivergence($line, $lastCloses, true);
                    if($line->getType() != DivergenceTypes::NO_DIVERGENCE) {
                        $divergenceLines[] = $line;
                    }
                }
            }
        }

        $orderedRSIPointsDesc = $rsiPoints->getOrderedList(true);

        foreach($orderedRSIPointsDesc as $highPoint) {
            if($highPoint->getPeriod() >= self::MIN_CANDLE_DIFFERENCE_DIVERGENCE) {
                $line = $rsiPoints->getValidLine($highPoint->getPeriod(), false);
                if($line) {
                    $this->checkDivergence($line, $lastCloses, false);
                    if($line->getType() != DivergenceTypes::NO_DIVERGENCE) {
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
     * @param $period1
     * @param $period1Prior
     * @param $period2
     * @param $period2Prior
     * @return StrategyResult
     */
    private function checkCrossMovingAverages($period1, $period1Prior, $period2, $period2Prior) : StrategyResult
    {
        $result = new StrategyResult();

        if($period1 > $period2 && $period1Prior <= $period2Prior){
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        }
        if($period1 < $period2 && $period1Prior >= $period2Prior){
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param DivergenceLine $line
     * @param array $lastCloses
     * @param $lower
     */
    private function checkDivergence(DivergenceLine $line, array $lastCloses, $lower)
    {
        $firstPeriod = $line->getFirstPoint()->getPeriod();
        $secondPeriod = $line->getSecondPoint()->getPeriod();

        $firstPeriodClose = $lastCloses[$firstPeriod];
        $secondPeriodClose = $lastCloses[$secondPeriod];

        $priceClosePercentageChange = ($secondPeriodClose / $firstPeriodClose) * 100;
        $indicatorPercentageChange = $line->getPercentageChange();

        $indicatorUpPriceDown = $indicatorPercentageChange < 100 && $priceClosePercentageChange > 100;
        $indicatorDownPriceUp = $indicatorPercentageChange > 100 && $priceClosePercentageChange < 100;

        $line->setPercentageDivergenceWithPrice(abs($priceClosePercentageChange - $indicatorPercentageChange));
        if($line->getPercentageDivergenceWithPrice() > 20) {
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


    /**
     * TODO buy/sell after downtrend/uptrend with high volume when volume decreases
     * @return StrategyResult
     */
    public function volumeSwings()
    {
        return new StrategyResult();
    }

    /**
     * TODO buy after consecutive higher volumes, sell after consecutive lower volumes
     * @return StrategyResult
     */
    public function volumeBreakout()
    {
        return new StrategyResult();
    }

    /**
     * @param $tradePrice
     * @param $percentage
     * @return StrategyResult
     */
    public function stopLosses($tradePrice, $percentage)
    {
        $result = new StrategyResult();

        $stopLossPrice = $tradePrice * (1-($percentage/100));
        if($this->currentPrice <= $stopLossPrice) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param $tradePrice
     * @param $percentage
     * @return StrategyResult
     */
    public function takeProfit($tradePrice, $percentage)
    {
        $result = new StrategyResult();

        $takeProfitPrice = $tradePrice * (1+($percentage/100));
        if($this->currentPrice >= $takeProfitPrice) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param TrendLine[] $trendLines
     * @return StrategyResult
     */
    public function supportAndResistance($trendLines)
    {
        $linesMetResult = $this->supportAndResistanceLinesMet($trendLines);
        $breakoutsResult = $this->supportAndResistanceLinesBreakouts($trendLines);

        if(!$breakoutsResult->noTrade()) {
            return $breakoutsResult;
        } else {
            return $linesMetResult;
        }

    }

    /**
 * @param $trendLines
 * @return StrategyResult
 */
    private function supportAndResistanceLinesMet($trendLines)
    {
        $result = new StrategyResult();
        foreach($trendLines as $trendLine) {
            if(!$this->checkTrendLineTime($trendLine)) {
                continue;
            }
            $price = $this->getTrendLinePrice($trendLine);

            if($trendLine->getType() == TrendLine::TYPE_SUPPORT) {
                if($this->currentPrice <= $price) {
                    $result->setTradeResult(StrategyResult::TRADE_LONG);
                }
            } else if($trendLine->getType() == TrendLine::TYPE_RESISTANCE) {
                if($this->currentPrice >= $price) {
                    $result->setTradeResult(StrategyResult::TRADE_SHORT);
                }
            }
        }
        return $result;
    }

    /**
     * @param $trendLines
     * @return StrategyResult
     */
    private function supportAndResistanceLinesBreakouts($trendLines)
    {
        $result = new StrategyResult();
        foreach($trendLines as $trendLine) {
            if(!$this->checkTrendLineTime($trendLine)) {
                continue;
            }
            $price = $this->getTrendLinePrice($trendLine);

            if($trendLine->getType() == TrendLine::TYPE_SUPPORT) {
                if($this->currentPrice <= ($price * 0.97)) {
                    $result->setTradeResult(StrategyResult::TRADE_SHORT);
                }
            } else if($trendLine->getType() == TrendLine::TYPE_RESISTANCE) {
                if($this->currentPrice >= ($price * 1.03)) {
                    $result->setTradeResult(StrategyResult::TRADE_LONG);
                }
            }
        }
        return $result;
    }

    /**
     * @param TrendLine $trendLine
     * @return float
     */
    private function getTrendLinePrice(TrendLine $trendLine)
    {
        $timeRange = $trendLine->getEndTime() - $trendLine->getStartTime();
        $priceRange = $trendLine->getEndPrice() - $trendLine->getStartPrice();
        $timeDifference = $this->currentClose - $trendLine->getStartTime();

        return $trendLine->getStartPrice() + (($timeDifference / $timeRange) * $priceRange);
    }

    /**
     * @param TrendLine $trendLine
     * @return bool
     */
    private function checkTrendLineTime(TrendLine $trendLine)
    {
        return $this->currentClose >= $trendLine->getStartTime() && $this->currentClose <= $trendLine->getEndTime();
    }

    /**
     * @param Candle[] $candles
     */
    public function setData($candles)
    {
        $data = [];
        /** @var Candle $candle */
        foreach($candles as $candle) {
            $data['open'][] = $candle->getOpenPrice();
            $data['close'][] = $candle->getClosePrice();
            $data['open_time'][] = $candle->getOpenTime();
            $data['close_time'][] = $candle->getCloseTime();
        }
        $this->data = $data;
        $this->currentPrice = $candle->getClosePrice();
        $this->currentClose = $candle->getCloseTime();
    }

    /**
     * @return array
     */
    public function getStrategiesList()
    {
        return self::STRATEGY_LIST;
    }

    /**
     * @param BotAlgorithm $algo
     * @return bool|StrategyResult
     */
    public function runStrategy(BotAlgorithm $algo)
    {
        $strategy = $algo->getStrategy();

        if(!in_array($strategy, self::STRATEGY_LIST)) {
            return false;
        }

        if($strategy == StrategyTypes::EMA_CROSSOVER) {
            $config = $algo->getEmaCrossoverConfig();
            if(!$config) {
                return false;
            }
            return $this->emaCrossover($config->getSmallPeriod(), $config->getLongPeriod());
        } else if($strategy == StrategyTypes::RSI_BOLLINGER || StrategyTypes::RSI_MACD) {
            $config = $algo->getRsiConfig();
            if(!$config) {
                return false;
            }
            return call_user_func(array($this,$strategy), $config->getSellOver(), $config->getBuyUnder());
        } else  {
            return call_user_func(array($this,$strategy));
        }
    }
}