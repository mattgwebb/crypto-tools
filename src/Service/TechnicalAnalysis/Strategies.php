<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\Candle;
use App\Entity\TechnicalAnalysis\DivergenceIndicators;
use App\Entity\TechnicalAnalysis\DivergenceLine;
use App\Entity\TechnicalAnalysis\DivergenceTypes;
use App\Entity\TechnicalAnalysis\IndicatorPoint;
use App\Entity\TechnicalAnalysis\IndicatorPointList;
use App\Entity\Algorithm\StrategyResult;
use App\Entity\Algorithm\StrategyTypes;
use App\Entity\TechnicalAnalysis\IndicatorTypes;
use App\Entity\TechnicalAnalysis\TrendLine;
use App\Exceptions\TechnicalAnalysis\IndicatorNotSupported;

class Strategies
{

    const STRATEGY_LIST = [
        StrategyTypes::RSI,
        StrategyTypes::BOLLINGER_BANDS,
        StrategyTypes::MACD,
        StrategyTypes::RSI_BOLLINGER,
        StrategyTypes::RSI_MACD,
        StrategyTypes::MACD_BOLLINGER,
        StrategyTypes::SUPPORT_RESISTANCE,
        StrategyTypes::RSI_DIVERGENCE,
        StrategyTypes::OBV_DIVERGENCE,
        StrategyTypes::EMA_SCALP,
        StrategyTypes::EMA_CROSSOVER,
        StrategyTypes::MA_CROSSOVER,
        StrategyTypes::ADAPTIVE_PQ,
        StrategyTypes::STOCH
    ];

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

    /**
     * @param float $rsiSell
     * @param float $rsiBuy
     * @param int $period
     * @return StrategyResult
     */
    public function rsi(float $rsiSell = 70.00, float $rsiBuy = 30.00, int $period = 14) : StrategyResult
    {
        $result = new StrategyResult();
        $rsi = $this->indicators->rsi($this->data, $period);

        if($rsi < $rsiBuy) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($rsi > $rsiSell) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        } else {
            $result->setTradeResult(StrategyResult::NO_TRADE);
        }
        return $result;
    }

    /**
     * @return StrategyResult
     */
    public function bollingerBands() : StrategyResult
    {
        $result = new StrategyResult();
        list($highBand, $lowBand) = $this->indicators->bollingerBands($this->data);

        if($this->currentPrice < $lowBand) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($this->currentPrice > $highBand) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        } else {
            $result->setTradeResult(StrategyResult::NO_TRADE);
        }
        return $result;
    }

    /**
     * @return StrategyResult
     */
    public function macd()
    {
        $result = new StrategyResult();
        $macd = $this->indicators->macd($this->data);

        if($macd > 0) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param float $rsiSell
     * @param float $rsiBuy
     * @param int $period
     * @return StrategyResult
     */
    public function rsiAndBollinger(float $rsiSell = 70.00, float $rsiBuy = 30.00, int $period = 14) : StrategyResult
    {
        $result = new StrategyResult();

        $bollingerResult = $this->bollingerBands();
        $rsiResult = $this->rsi($rsiSell, $rsiBuy, $period);

        if($rsiResult->isLong() && $bollingerResult->isLong()) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($rsiResult->isShort() && $bollingerResult->isShort()) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param float $rsiSell
     * @param float $rsiBuy
     * @param int $period
     * @return StrategyResult
     */
    public function rsiAndMacd(float $rsiSell = 70.00, float $rsiBuy = 30.00, int $period = 14) : StrategyResult
    {
        $result = new StrategyResult();

        $macdResult = $this->macd();
        $rsiResult = $this->rsi($rsiSell, $rsiBuy, $period);

        if($macdResult->isLong() && $rsiResult->isLong()) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($macdResult->isShort() && $rsiResult->isShort()) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }

        return $result;
    }

    /**
     * @return StrategyResult
     */
    public function macdAndBollinger() : StrategyResult
    {
        $result = new StrategyResult();

        $bollingerResult = $this->bollingerBands();
        $macdResult = $this->macd();

        if($macdResult->isLong() && $bollingerResult->isLong()) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($macdResult->isShort() && $bollingerResult->isShort()) {
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

        //error_log("period 1 $period1EMA period 1 prior $period1PriorEMA period 2 $period2EMA period 2 prior $period2PriorEMA");

        return $this->checkCrossMovingAverages($period1EMA, $period1PriorEMA, $period2EMA, $period2PriorEMA);
    }

    /**
     * @param int $period1
     * @param int $period2
     * @return StrategyResult
     */
    public function maCrossover($period1 = 10, $period2 = 50) : StrategyResult
    {
        $period1MA = $this->indicators->ma($this->data['close'], $period1);
        $period1PriorMA = $this->indicators->ma($this->data['close'], $period1, 1); // prior

        $period2MA = $this->indicators->ma($this->data['close'], $period2);
        $period2PriorMA = $this->indicators->ma($this->data['close'], $period2, 1); // prior

        return $this->checkCrossMovingAverages($period1MA, $period1PriorMA, $period2MA, $period2PriorMA);
    }

    /**
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @return StrategyResult
     */
    public function rsiDivergence(int $previousCandles = 10, int $minCandleDifference = 2, int $minDivergencePercentage = 20): StrategyResult
    {
        return $this->indicatorDivergence(DivergenceIndicators::RSI, $previousCandles, $minCandleDifference, $minDivergencePercentage);
    }

    /**
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @return StrategyResult
     */
    public function obvDivergence(int $previousCandles = 10, int $minCandleDifference = 2, int $minDivergencePercentage = 20): StrategyResult
    {
        return $this->indicatorDivergence(DivergenceIndicators::OBV, $previousCandles, $minCandleDifference, $minDivergencePercentage);
    }

    /**
     * @param float $p
     * @param float $q
     * @param string $oscillator
     * @param string $ma
     * @param int $maPeriod
     * @return StrategyResult
     * @throws IndicatorNotSupported
     */
    public function adaptivePQ(float $p = 40.00, float $q = 60.00, string $oscillator = IndicatorTypes::RSI,
                               string $ma = IndicatorTypes::EMA, int $maPeriod = 20) : StrategyResult
    {
        $result = new StrategyResult();

        if($oscillator == IndicatorTypes::RSI) {
            $oscillatorPeriodData = $this->indicators->rsiPeriod($this->data);
            $oscillatorPeriodData = array_values($oscillatorPeriodData);
        } else {
            throw new IndicatorNotSupported();
        }

        if($ma == IndicatorTypes::EMA) {
            $oscillatorMa = $this->indicators->ema($oscillatorPeriodData, $maPeriod);
            $oscillatorLastValue = array_pop($oscillatorPeriodData);
        } else {
            throw new IndicatorNotSupported();
        }

        if($oscillatorMa > $p && $oscillatorMa < $q) {
            if($oscillatorLastValue >= $oscillatorMa) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else  {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }


    /**
     * @param int $stochBuy
     * @param int $stochSell
     * @return StrategyResult
     */
    public function stoch(int $stochBuy = 20, int $stochSell = 80) : StrategyResult
    {
        $result = new StrategyResult();
        $stoch = $this->indicators->stoch($this->data);

        if($stoch < $stochBuy) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($stoch > $stochSell) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param int $type
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @return StrategyResult
     */
    private function indicatorDivergence(int $type, int $previousCandles, int $minCandleDifference, int $minDivergencePercentage): StrategyResult
    {
        $result = new StrategyResult();

        if($type == DivergenceIndicators::RSI) {
            $indicatorPeriod = $this->indicators->rsiPeriod($this->data);
        } else if($type == DivergenceIndicators::OBV) {
            $indicatorPeriod = $this->indicators->obvPeriod($this->data);
        } else return $result;

        $indicatorPeriod = array_values($indicatorPeriod);
        $indicatorPeriod = array_slice($indicatorPeriod, $previousCandles * (-1));
        $indicatorPeriod = array_reverse($indicatorPeriod);

        $lastOpenTimes = array_slice($this->data['open_time'], $previousCandles * (-1));
        $lastOpenTimes = array_reverse($lastOpenTimes);

        $lastCloses = array_slice($this->data['close'], $previousCandles * (-1));
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
                    if($line->getType() != DivergenceTypes::NO_DIVERGENCE) {
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
            $data['volume'][] = $candle->getVolume();
            $data['high'][] = $candle->getHighPrice();
            $data['low'][] = $candle->getLowPrice();
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

        if($strategy == StrategyTypes::EMA_CROSSOVER || $strategy == StrategyTypes::MA_CROSSOVER) {
            $config = $algo->getMaCrossoverConfig();
            if(!$config) {
                return false;
            }
            return call_user_func(array($this,$strategy), $config->getSmallPeriod(), $config->getLongPeriod());
        } else if($strategy == StrategyTypes::RSI_BOLLINGER || $strategy == StrategyTypes::RSI_MACD) {
            $config = $algo->getRsiConfig();
            if(!$config) {
                return false;
            }
            return call_user_func(array($this,$strategy), $config->getSellOver(), $config->getBuyUnder(), $config->getPeriod());
        } else if($strategy == StrategyTypes::RSI_DIVERGENCE || $strategy == StrategyTypes::OBV_DIVERGENCE) {
            $config = $algo->getDivergenceConfig();
            if(!$config) {
                return false;
            }
            return call_user_func(array($this,$strategy), $config->getLastCandles(), $config->getMinCandleDifference(),
                $config->getMinDivergencePercentage());
        } else if($strategy == StrategyTypes::ADAPTIVE_PQ) {
            $config = $algo->getAdaptivePQConfig();
            if(!$config) {
                return false;
            }
            return call_user_func(array($this,$strategy), $config->getPValue(), $config->getQValue(),
                $config->getOscillatorIndicator(), $config->getMaIndicator(), $config->getMaPeriod());
        } else {
            return call_user_func(array($this,$strategy));
        }
    }
}