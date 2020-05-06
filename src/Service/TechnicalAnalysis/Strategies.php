<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Algorithm\StrategyCombination;
use App\Entity\Algorithm\StrategyConfig;
use App\Entity\Data\Candle;
use App\Entity\Algorithm\StrategyResult;
use App\Entity\Algorithm\StrategyTypes;
use App\Entity\TechnicalAnalysis\IndicatorTypes;
use App\Entity\TechnicalAnalysis\PivotPoint;
use App\Entity\TechnicalAnalysis\PivotTypes;
use App\Entity\TechnicalAnalysis\Strategy;
use App\Entity\TechnicalAnalysis\TrendLine;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\Algorithm\StrategyNotFoundException;
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
     * @var DivergenceStrategies
     */
    private $divergenceStrategies;

    /**
     * @var MovingAverageStrategies
     */
    private $movingAverageStrategies;

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
     * @var float
     */
    private $currentTradePrice;

    /**
     * @var array
     */
    private $candles;

    /**
     * Strategies constructor.
     * @param Indicators $indicators
     * @param StrategyLanguageParser $strategyLanguageParser
     * @param DivergenceStrategies $divergenceStrategies
     * @param MovingAverageStrategies $movingAverageStrategies
     */
    public function __construct(Indicators $indicators, StrategyLanguageParser $strategyLanguageParser,
                                DivergenceStrategies $divergenceStrategies, MovingAverageStrategies $movingAverageStrategies)
    {
        $this->indicators = $indicators;
        $this->strategyLanguageParser = $strategyLanguageParser;
        $this->divergenceStrategies = $divergenceStrategies;
        $this->movingAverageStrategies = $movingAverageStrategies;
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
        return $this->movingAverageStrategies->ma($this->data, $this->currentPrice, $period);
    }

    /**
     * @param int $period
     * @return StrategyResult
     */
    public function ema(int $period = 20) : StrategyResult
    {
        return $this->movingAverageStrategies->ema($this->data, $this->currentPrice, $period);
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
        return $this->movingAverageStrategies->macd($this->data);
    }

    /**
     * @return StrategyResult
     */
    public function emaScalp() : StrategyResult
    {
        return $this->movingAverageStrategies->emaScalp($this->data);
    }

    /**
     * @param int $period1
     * @param int $period2
     * @return StrategyResult
     */
    public function emaCrossover($period1 = 10, $period2 = 20) : StrategyResult
    {
        return $this->movingAverageStrategies->emaCrossover($this->data, $period1, $period2);
    }

    /**
     * @param int $period1
     * @param int $period2
     * @return StrategyResult
     */
    public function maCrossover($period1 = 10, $period2 = 50) : StrategyResult
    {
        return $this->movingAverageStrategies->maCrossover($this->data, $period1, $period2);

    }

    /**
     * @return StrategyResult
     */
    public function guppyCrossover() : StrategyResult
    {
        return $this->movingAverageStrategies->guppyCrossover($this->data);
    }

    /**
     * @param string $indicator
     * @param int $previousCandles
     * @param int $minCandleDifference
     * @param int $minDivergencePercentage
     * @param bool $regularDivergence
     * @param bool $hiddenDivergence
     * @return StrategyResult
     */
    public function divergence(string $indicator, int $previousCandles = 10, int $minCandleDifference = 2,
                                        int $minDivergencePercentage = 20, bool $regularDivergence = true,
                                        bool $hiddenDivergence = true): StrategyResult
    {
        return $this->divergenceStrategies->indicatorDivergence($this->data, $indicator, $previousCandles, $minCandleDifference,
            $minDivergencePercentage, $regularDivergence, $hiddenDivergence);
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
     * @param int $mfiSell
     * @param int $mfiBuy
     * @param int $period
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function mfi(int $mfiSell = 80, int $mfiBuy = 20, int $period = 14, bool $crossOnly = false) : StrategyResult
    {
        $result = new StrategyResult();
        $mfi = $this->indicators->mfi($this->data, $period);

        if($crossOnly) {
            $mfiPrior = $this->indicators->mfi($this->data, $period, true);

            if($mfi >= $mfiBuy && $mfiPrior < $mfiBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($mfi <= $mfiSell && $mfiPrior > $mfiSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        } else {
            if($mfi < $mfiBuy) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($mfi > $mfiSell) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
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
     * @param $percentage
     * @return StrategyResult
     */
    public function stopLoss($percentage)
    {
        $result = new StrategyResult();

        if(!$this->currentTradePrice) {
            return $result;
        }

        $stopLossPrice = $this->currentTradePrice * (1-($percentage/100));
        if($this->currentPrice <= $stopLossPrice) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }
        return $result;
    }

    /**
     * @param $percentage
     * @return StrategyResult
     */
    public function takeProfit($percentage)
    {
        $result = new StrategyResult();

        if(!$this->currentTradePrice) {
            return $result;
        }

        $takeProfitPrice = $this->currentTradePrice * (1+($percentage/100));
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
     * @param float $currentTradePrice
     */
    public function setCurrentTradePrice(float $currentTradePrice): void
    {
        $this->currentTradePrice = $currentTradePrice;
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
     * @throws StrategyNotFoundException
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

        $strategyResult = $this->getStrategyResult($strategies, $currentTradeType);

        if($currentTradeType == TradeTypes::TRADE_BUY && !$strategyResult->isShort()
            && $algo->getInvalidationStrategyCombination()) {
            $strategies = $this->strategyLanguageParser->getStrategies($algo->getInvalidationStrategyCombination());
            $strategyResult = $this->getStrategyResult($strategies, $currentTradeType);
            $strategyResult->setFromInvalidation(true);
        }

        return $strategyResult;
    }

    /**
     * @param StrategyCombination $strategies
     * @param int $currentTradeType
     * @return StrategyResult
     * @throws StrategyNotFoundException
     */
    private function getStrategyResult(StrategyCombination $strategies, int $currentTradeType)
    {
        $strategyResult = new StrategyResult();

        $results = [
            StrategyResult::TRADE_SHORT => 0,
            StrategyResult::NO_TRADE => 0,
            StrategyResult::TRADE_LONG => 0
        ];

        /** @var StrategyConfig $strategy */
        foreach($strategies->getStrategyConfigList() as $strategy) {
            $result = $this->runStrategy($strategy);
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
     * @param StrategyConfig $strategyConfig
     * @return StrategyResult
     * @throws StrategyNotFoundException
     */
    private function runStrategy(StrategyConfig $strategyConfig)
    {
        if(!method_exists($this, $strategyConfig->getStrategy()->getName())) {
            throw new StrategyNotFoundException();
        }
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