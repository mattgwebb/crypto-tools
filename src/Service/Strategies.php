<?php


namespace App\Service;


use App\Entity\Candle;
use App\Entity\DivergenceLine;
use App\Entity\IndicatorPoint;
use App\Entity\IndicatorPointList;
use App\Entity\StrategyResult;
use App\Entity\TrendLine;
use Doctrine\Common\Collections\ArrayCollection;
use phpDocumentor\Reflection\Types\Self_;
use Symfony\Bundle\MakerBundle\Str;

class Strategies
{

    const STRATEGY_LIST = ["rsiAndBollinger", "rsiAndMacd", "supportAndResistance", "rsiDivergence"];

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

    public function rsiDivergence(int $previousCandles = 10): StrategyResult
    {
        $result = new StrategyResult();

        $rsiPeriod = $this->indicators->rsiPeriod($this->data);

        $rsiPeriod = array_values($rsiPeriod);
        $rsiPeriod = array_slice($rsiPeriod, $previousCandles * (-1));
        $rsiPeriod = array_reverse($rsiPeriod);
        $rsiPoints = new IndicatorPointList($rsiPeriod);

        $orderedRSIPointsAsc = $rsiPoints->getOrderedList();

        $lastCloses = array_slice($this->data['close'], $previousCandles * (-1));
        $lastCloses = array_reverse($lastCloses);

        foreach($orderedRSIPointsAsc as $lowPoint) {
            if($lowPoint->getPeriod() >= self::MIN_CANDLE_DIFFERENCE_DIVERGENCE) {
                $line = $rsiPoints->getValidLine($lowPoint->getPeriod(), true);
                if($line) {
                    $this->checkDivergence($line, true, $lastCloses);
                }
            }
        }

        $orderedRSIPointsDesc = $rsiPoints->getOrderedList(true);

        foreach($orderedRSIPointsDesc as $highPoint) {
            if($highPoint->getPeriod() >= self::MIN_CANDLE_DIFFERENCE_DIVERGENCE) {
                $line = $rsiPoints->getValidLine($highPoint->getPeriod(), false);
                if($line) {
                    $this->checkDivergence($line, false, $lastCloses);
                }
            }
        }

        return $result;
    }

    private function checkDivergence(DivergenceLine $line, bool $lower, array $lastCloses)
    {
        /** TODO implement method */
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
     * @param $strategy
     * @return bool|StrategyResult
     */
    public function runStrategy($strategy)
    {
        if(!in_array($strategy, self::STRATEGY_LIST)) {
            return false;
        }
        return call_user_func(array($this,$strategy));
    }
}