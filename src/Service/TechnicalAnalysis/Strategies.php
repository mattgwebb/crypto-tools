<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Algorithm\StrategyCombination;
use App\Entity\Algorithm\StrategyConfig;
use App\Entity\Data\Candle;
use App\Entity\TechnicalAnalysis\DivergenceIndicators;
use App\Entity\TechnicalAnalysis\DivergenceLine;
use App\Entity\TechnicalAnalysis\DivergenceTypes;
use App\Entity\TechnicalAnalysis\IndicatorPointList;
use App\Entity\Algorithm\StrategyResult;
use App\Entity\Algorithm\StrategyTypes;
use App\Entity\TechnicalAnalysis\IndicatorTypes;
use App\Entity\TechnicalAnalysis\PivotPoint;
use App\Entity\TechnicalAnalysis\PivotTypes;
use App\Entity\TechnicalAnalysis\Strategy;
use App\Entity\TechnicalAnalysis\TrendLine;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\TechnicalAnalysis\IndicatorNotSupported;
use App\Service\Algorithm\StrategyLanguageParser;

class Strategies
{

    const STRATEGY_LIST = [
        StrategyTypes::RSI,
        StrategyTypes::MA,
        StrategyTypes::EMA,
        StrategyTypes::BOLLINGER_BANDS,
        StrategyTypes::MACD,
        StrategyTypes::SUPPORT_RESISTANCE,
        StrategyTypes::RSI_DIVERGENCE,
        StrategyTypes::OBV_DIVERGENCE,
        StrategyTypes::CHAIKIN_DIVERGENCE,
        StrategyTypes::EMA_SCALP,
        StrategyTypes::EMA_CROSSOVER,
        StrategyTypes::MA_CROSSOVER,
        StrategyTypes::ADAPTIVE_PQ,
        StrategyTypes::ADX_DMI,
        StrategyTypes::ADX_MOM,
        StrategyTypes::STOCH,
        StrategyTypes::GUPPY_CROSSOVER
    ];

    /**
     * @var Indicators
     */
    private $indicators;

    /**
     * @var StrategyLanguageParser
     */
    private $strategyLanguageParser;

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
     * @var array
     */
    private $candles;

    /**
     * Strategies constructor.
     * @param Indicators $indicators
     * @param StrategyLanguageParser $strategyLanguageParser
     */
    public function __construct(Indicators $indicators, StrategyLanguageParser $strategyLanguageParser)
    {
        $this->indicators = $indicators;
        $this->strategyLanguageParser = $strategyLanguageParser;
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
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function rsi(float $rsiSell = 70.00, float $rsiBuy = 30.00, int $period = 14, bool $crossOnly = false) : StrategyResult
    {
        $result = new StrategyResult();
        $rsi = $this->indicators->rsi($this->data, $period);

        if($crossOnly) {
            $rsiPrior = $this->indicators->rsi($this->data, $period, true);

            if($rsi >= $rsiBuy && $rsiPrior < $rsiBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($rsi <= $rsiSell && $rsiPrior > $rsiSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        } else {
            if($rsi < $rsiBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($rsi > $rsiSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }

    /**
     * @param int $period
     * @return StrategyResult
     */
    public function ma(int $period = 20) : StrategyResult
    {
        $result = new StrategyResult();
        $ma = $this->indicators->ma($this->data['close'], $period);

        if($this->currentPrice > $ma) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($this->currentPrice < $ma) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param int $period
     * @return StrategyResult
     */
    public function ema(int $period = 20) : StrategyResult
    {
        $result = new StrategyResult();
        $ema = $this->indicators->ema($this->data['close'], $period);

        if($this->currentPrice > $ema) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($this->currentPrice < $ema) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
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
     * @return StrategyResult
     */
    public function guppyCrossover() : StrategyResult
    {
        $result = new StrategyResult();

        $shortPeriods = [3,5,8,10,12,15];
        $longPeriods = [30,35,40,45,50,60];

        $currentShortEMAs = $priorShortEMAs = $currentLongEMAs = $priorLongEMAs = [];

        foreach ($shortPeriods as $shortPeriod) {
            $currentShortEMAs[] = $this->indicators->ema($this->data['close'], $shortPeriod);
            $priorShortEMAs[] = $this->indicators->ema($this->data['close'], $shortPeriod, 1);
        }

        foreach ($longPeriods as $longPeriod) {
            $currentLongEMAs[] = $this->indicators->ema($this->data['close'], $longPeriod);
            $priorLongEMAs[] = $this->indicators->ema($this->data['close'], $longPeriod, 1);
        }

        $minShortEMA = min($currentShortEMAs);
        $maxShortEMA = max($currentShortEMAs);

        $minPriorShortEMA = min($priorShortEMAs);
        $maxPriorShortEMA = max($priorShortEMAs);

        $minLongEMA = min($currentLongEMAs);
        $maxLongEMA = max($currentLongEMAs);

        $minPriorLongEMA = min($priorLongEMAs);
        $maxPriorLongEMA = max($priorLongEMAs);

        if($minShortEMA >= $maxLongEMA) {
            if($minPriorShortEMA < $maxPriorLongEMA) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            }
        } else if($minLongEMA >= $maxShortEMA) {
            if($minPriorLongEMA < $maxPriorShortEMA) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }

    /**
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @param bool $regularDivergence
     * @param bool $hiddenDivergence
     * @return StrategyResult
     */
    public function rsiDivergence(int $previousCandles = 10, int $minCandleDifference = 2, int $minDivergencePercentage = 20,
                                  bool $regularDivergence = true, bool $hiddenDivergence = true): StrategyResult
    {
        return $this->indicatorDivergence(DivergenceIndicators::RSI, $previousCandles, $minCandleDifference, $minDivergencePercentage,
            $regularDivergence, $hiddenDivergence);
    }

    /**
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @param bool $regularDivergence
     * @param bool $hiddenDivergence
     * @return StrategyResult
     */
    public function chaikinDivergence(int $previousCandles = 10, int $minCandleDifference = 2, int $minDivergencePercentage = 20,
                                  bool $regularDivergence = true, bool $hiddenDivergence = true): StrategyResult
    {
        return $this->indicatorDivergence(DivergenceIndicators::CHAIKIN, $previousCandles, $minCandleDifference, $minDivergencePercentage,
            $regularDivergence, $hiddenDivergence);
    }

    /**
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @param bool $regularDivergence
     * @param bool $hiddenDivergence
     * @return StrategyResult
     */
    public function obvDivergence(int $previousCandles = 10, int $minCandleDifference = 2, int $minDivergencePercentage = 20,
                                  bool $regularDivergence = true, bool $hiddenDivergence = true): StrategyResult
    {
        return $this->indicatorDivergence(DivergenceIndicators::OBV, $previousCandles, $minCandleDifference, $minDivergencePercentage,
            $regularDivergence, $hiddenDivergence);
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
     * TODO implement strategy
     * @param int $period
     * @return StrategyResult
     */
    public function adxDmi(int $period = 14) : StrategyResult
    {
        $result = new StrategyResult();

        $dmi = $this->indicators->dmiPeriod($this->data, $period);
        $adx = $this->indicators->adxPeriod($this->data, $period);

        return $result;
    }

    /**
     * @param int $adxPeriod
     * @param int $momPeriod
     * @return StrategyResult
     */
    public function adxMom(int $adxPeriod = 25, int $momPeriod = 14) : StrategyResult
    {
        $result = new StrategyResult();

        $adx = $this->indicators->adx($this->data, $adxPeriod);
        $mom = $this->indicators->mom($this->data, $momPeriod);
        $fsar = $this->indicators->fsar($this->data);

        if ($adx > 25 && $mom > 100 && $fsar > 0) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        }
        if ($adx > 25 && $mom < 100 && $fsar < 0) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param int $stochBuy
     * @param int $stochSell
     * @param int $period
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function stoch(int $stochSell = 80, int $stochBuy = 20, int $period = 14, bool $crossOnly = false) : StrategyResult
    {
        $result = new StrategyResult();
        $stoch = $this->indicators->stoch($this->data, $period, 3);

        if($crossOnly) {
            $stochPrior = $this->indicators->stoch($this->data, $period, 3,true);

            if($stoch >= $stochBuy && $stochPrior < $stochBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($stoch <= $stochSell && $stochPrior > $stochSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        } else {
            if($stoch < $stochBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($stoch > $stochSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }

    /**
     * @param int $type
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @param bool $regularDivergence
     * @param bool $hiddenDivergence
     * @return StrategyResult
     */
    private function indicatorDivergence(int $type, int $previousCandles, int $minCandleDifference, int $minDivergencePercentage,
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

        if($type == DivergenceIndicators::RSI) {
            $indicatorPeriod = $this->indicators->rsiPeriod($this->data);
        } else if($type == DivergenceIndicators::OBV) {
            $indicatorPeriod = $this->indicators->obvPeriod($this->data);
        } else if($type == DivergenceIndicators::CHAIKIN) {
            $indicatorPeriod = $this->indicators->chaikinOscillatorPeriod($this->data);
        } else {
            return $result;
        }

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
     * @return StrategyResult
     */
    public function supportAndResistance()
    {
        $trendLines = $this->detectTrendLines();
        $linesMetResult = $this->supportAndResistanceLinesMet($trendLines);
        //$breakoutsResult = $this->supportAndResistanceLinesBreakouts($trendLines);
        return $linesMetResult;
    }

    /**
     * @return array
     */
    public function detectTrendLines()
    {
        $trendLines = [];
        $pivotPoints = $this->getPivotPoints();
        $lines = $this->getLinePoints($pivotPoints);

        foreach($lines as $pivotTouches) {
            if(count($pivotTouches) < 2) {
                continue;
            }
            $lineTouches = array_values($pivotTouches);

            $trendLines[] = $this->newTrendLine($lineTouches);
        }
        return $trendLines;
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
     * @return array
     */
    private function getPivotPoints()
    {
        $pivotPoints = [];

        foreach($this->candles as $key => $candle) {
            if(isset($this->candles[$key - 2]) && isset($this->candles[$key + 2])) {

                $previousClose = $this->candles[$key - 1]->getClosePrice();
                $nextClose = $this->candles[$key + 1]->getClosePrice();
                $previousPreviousClose = $this->candles[$key - 2]->getClosePrice();
                $nextNextClose = $this->candles[$key + 2]->getClosePrice();

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
     * @param $trendLines
     * @return StrategyResult
     */
    private function supportAndResistanceLinesMet($trendLines)
    {
        $candles = count($this->data['close']);
        $previousPrice = $this->data['close'][$candles - 2];
        $currentPrice = $this->currentPrice;

        $result = new StrategyResult();

        /** @var TrendLine $trendLine */
        foreach($trendLines as $trendLine) {
            if(!$this->checkTrendLineTime($trendLine)) {
                //continue;
            }
            $linePrice = $this->getTrendLinePrice($trendLine);

            $result->setExtraData(['trend_line' => $trendLine]);

            if($trendLine->getType() == TrendLine::TYPE_SUPPORT) {
                if($currentPrice <= $linePrice && $previousPrice > $linePrice) {
                    $result->setTradeResult(StrategyResult::TRADE_LONG);
                    return $result;
                }
            } else if($trendLine->getType() == TrendLine::TYPE_RESISTANCE) {
                if($currentPrice >= $linePrice && $previousPrice < $linePrice) {
                    $result->setTradeResult(StrategyResult::TRADE_SHORT);
                    return $result;
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
        if($trendLine->getStartPrice() == $trendLine->getEndPrice()) {
            return $trendLine->getStartPrice();
        }
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
        $this->candles = $candles;
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
     * @param int $currentTradeType
     * @return StrategyResult
     */
    public function runStrategies(BotAlgorithm $algo, int $currentTradeType = TradeTypes::TRADE_SELL)
    {
        if($currentTradeType == TradeTypes::TRADE_SELL) {
            $strategies = $this->strategyLanguageParser->getStrategies($algo->getEntryStrategyCombination());
        } else if($currentTradeType == TradeTypes::TRADE_BUY) {
            $strategies = $this->strategyLanguageParser->getStrategies($algo->getExitStrategyCombination());
        } else {
            return new StrategyResult();
        }

        $strategyResult = $this->getStrategyResult($algo, $strategies, $currentTradeType);

        if($currentTradeType == TradeTypes::TRADE_BUY && !$strategyResult->isShort()
            && $algo->getInvalidationStrategyCombination()) {
            $strategies = $this->strategyLanguageParser->getStrategies($algo->getInvalidationStrategyCombination());
            $strategyResult = $this->getStrategyResult($algo, $strategies, $currentTradeType);
            $strategyResult->setFromInvalidation(true);
        }

        return $strategyResult;
    }

    /**
     * @param BotAlgorithm $algo
     * @param StrategyCombination $strategies
     * @param int $currentTradeType
     * @return StrategyResult
     */
    private function getStrategyResult(BotAlgorithm $algo, StrategyCombination $strategies, int $currentTradeType)
    {
        $strategyResult = new StrategyResult();

        $results = [
            StrategyResult::TRADE_SHORT => 0,
            StrategyResult::NO_TRADE => 0,
            StrategyResult::TRADE_LONG => 0
        ];

        /** @var StrategyConfig $strategy */
        foreach($strategies->getStrategyConfigList() as $strategy) {
            $result = $this->runStrategy($algo, $strategy);
            $results[$result->getTradeResult()]++;
        }

        $totalResults = array_sum($results);

        if($strategies->getOperator() == StrategyLanguageParser::AND_OPERATOR) {
            if($results[StrategyResult::TRADE_SHORT] == $totalResults) {
                $strategyResult->setTradeResult(StrategyResult::TRADE_SHORT);
            } else if($results[StrategyResult::TRADE_LONG] == $totalResults) {
                $strategyResult->setTradeResult(StrategyResult::TRADE_LONG);
            }
        } else if($strategies->getOperator() == StrategyLanguageParser::OR_OPERATOR) {
            if($currentTradeType == TradeTypes::TRADE_SELL && $results[StrategyResult::TRADE_LONG] >= 1) {
                $strategyResult->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($currentTradeType == TradeTypes::TRADE_BUY && $results[StrategyResult::TRADE_SHORT] >= 1) {
                $strategyResult->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $strategyResult;
    }

    /**
     * @param BotAlgorithm $algo
     * @param StrategyConfig $strategyConfig
     * @return StrategyResult
     */
    private function runStrategy(BotAlgorithm $algo, StrategyConfig $strategyConfig)
    {
        return call_user_func_array(array($this,$strategyConfig->getStrategy()->getName()), $strategyConfig->getConfigParams());
    }

//    /**
//     * @param BotAlgorithm $algo
//     * @param Strategy $strategy
//     * @return StrategyResult
//     */
//    private function runStrategy(BotAlgorithm $algo, Strategy $strategy)
//    {
//        $noResult = new StrategyResult();
//
//        $strategyName = $strategy->getName();
//
//        if(!in_array($strategyName, self::STRATEGY_LIST)) {
//            return $noResult;
//        }
//
//        if($strategy->isCrossoverStrategy()) {
//            return $this->runCrossoverStrategy($algo, $strategy);
//        } else if($strategy->isOscillatorStrategy()) {
//            return $this->runOscillatorStrategy($algo, $strategy);
//        } else if($strategy->isDivergenceStrategy()) {
//            return $this->runDivergenceStrategy($algo, $strategy);
//        } else if($strategyName == StrategyTypes::ADAPTIVE_PQ) {
//            return $this->runAdaptivePQStrategy($algo,$strategy);
//        } else if($strategy->isMovingAverageStrategy()) {
//            return $this->runMovingAverageStrategy($algo, $strategy);
//        } else {
//            return call_user_func(array($this,$strategyName));
//        }
//    }

    /**
     * @param BotAlgorithm $algo
     * @param Strategy $strategy
     * @return StrategyResult
     */
    private function runDivergenceStrategy(BotAlgorithm $algo, Strategy $strategy)
    {
        $config = $algo->getDivergenceConfig();
        if(!$config) {
            return new StrategyResult();
        }
        return call_user_func(array($this,$strategy->getName()), $config->getLastCandles(), $config->getMinCandleDifference(),
            $config->getMinDivergencePercentage(), $config->isRegularDivergences(), $config->isHiddenDivergences());
    }

    /**
     * @param BotAlgorithm $algo
     * @param Strategy $strategy
     * @return StrategyResult
     */
    private function runOscillatorStrategy(BotAlgorithm $algo, Strategy $strategy)
    {
        $config = $algo->getOscillatorConfig();
        if(!$config) {
            return new StrategyResult();
        }
        return call_user_func(array($this,$strategy->getName()), $config->getSellOver(), $config->getBuyUnder(),
            $config->getPeriod(), $config->isCrossOnly());
    }

    /**
     * @param BotAlgorithm $algo
     * @param Strategy $strategy
     * @return StrategyResult
     */
    private function runCrossoverStrategy(BotAlgorithm $algo, Strategy $strategy)
    {
        $config = $algo->getMaCrossoverConfig();
        if(!$config) {
            return new StrategyResult();
        }
        return call_user_func(array($this,$strategy->getName()), $config->getSmallPeriod(), $config->getLongPeriod());
    }

    /**
     * @param BotAlgorithm $algo
     * @param Strategy $strategy
     * @return StrategyResult
     */
    private function runAdaptivePQStrategy(BotAlgorithm $algo, Strategy $strategy)
    {
        $config = $algo->getAdaptivePQConfig();
        if(!$config) {
            return new StrategyResult();
        }
        return call_user_func(array($this,$strategy->getName()), $config->getPValue(), $config->getQValue(),
            $config->getOscillatorIndicator(), $config->getMaIndicator(), $config->getMaPeriod());
    }

    /**
     * @param BotAlgorithm $algo
     * @param Strategy $strategy
     * @return StrategyResult
     */
    private function runMovingAverageStrategy(BotAlgorithm $algo, Strategy $strategy)
    {
        $config = $algo->getMaConfig();
        if(!$config) {
            return new StrategyResult();
        }
        return call_user_func(array($this,$strategy->getName()), $config->getPeriod());
    }
}