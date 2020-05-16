<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Algorithm\StrategyCombination;
use App\Entity\Algorithm\StrategyConfig;
use App\Entity\Data\Candle;
use App\Entity\Algorithm\StrategyResult;
use App\Entity\Algorithm\StrategyTypes;
use App\Entity\TechnicalAnalysis\IndicatorTypes;
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
     * @var OscillatorStrategies
     */
    private $oscillatorStrategies;

    /**
     * @var TrendLineStrategies
     */
    private $trendLineStrategies;

    /**
     * @var DeviationStrategies
     */
    private $deviationStrategies;

    /**
     * @var array
     */
    private $data;

    /**
     * @var float
     */
    private $currentPrice;

    /**
     * @var float
     */
    private $priorPrice;

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
     * @param OscillatorStrategies $oscillatorStrategies
     * @param TrendLineStrategies $trendLineStrategies
     * @param DeviationStrategies $deviationStrategies
     */
    public function __construct(Indicators $indicators, StrategyLanguageParser $strategyLanguageParser,
                                DivergenceStrategies $divergenceStrategies, MovingAverageStrategies $movingAverageStrategies,
                                OscillatorStrategies $oscillatorStrategies, TrendLineStrategies $trendLineStrategies,
                                DeviationStrategies $deviationStrategies)
    {
        $this->indicators = $indicators;
        $this->strategyLanguageParser = $strategyLanguageParser;
        $this->divergenceStrategies = $divergenceStrategies;
        $this->movingAverageStrategies = $movingAverageStrategies;
        $this->oscillatorStrategies = $oscillatorStrategies;
        $this->trendLineStrategies = $trendLineStrategies;
        $this->deviationStrategies = $deviationStrategies;
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
        return $this->oscillatorStrategies->rsi($this->data, $rsiSell, $rsiBuy, $period, $crossOnly);
    }

    /**
     * @param int $period
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function ma(int $period = 20, bool $crossOnly = false) : StrategyResult
    {
        return $this->movingAverageStrategies->ma($this->data, $this->currentPrice, $this->priorPrice, $period, $crossOnly);
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
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function bollingerBands(bool $crossOnly = false) : StrategyResult
    {
        return $this->deviationStrategies->bollingerBands($this->data, $this->currentPrice, $this->priorPrice, $crossOnly);
    }

    /**
     * @param int $candlesAgo
     * @param int $period
     * @return StrategyResult
     */
    public function keltnerSqueeze(int $candlesAgo = 5, int $period = 20) : StrategyResult
    {
        return $this->deviationStrategies->keltnerSqueeze($this->data, $candlesAgo, $period, $this->currentPrice);
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
        return $this->oscillatorStrategies->stoch($this->data, $stochSell, $stochBuy, $period, $crossOnly);
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
        return $this->oscillatorStrategies->mfi($this->data, $mfiSell, $mfiBuy, $period, $crossOnly);
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
        return $this->trendLineStrategies->supportAndResistance($this->candles);
    }

    /**
     * @return array
     */
    public function detectTrendLines()
    {
        return $this->trendLineStrategies->detectTrendLines($this->candles);
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

        $count = count($candles);
        $this->currentPrice = $data['close'][$count - 1];
        $this->priorPrice = $data['close'][$count - 2];
        $this->currentClose = $data['close_time'][$count - 1];

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

            if($strategy->isReverseResult()) {
                $result = $this->getReverseResult($result);
            }

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

    /**
     * @param StrategyResult $result
     * @return StrategyResult
     */
    private function getReverseResult(StrategyResult $result)
    {
        if($result->isLong()) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        } else if($result->isShort()) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        }
        return $result;
    }
}