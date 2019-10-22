<?php


namespace App\Service;


use App\Entity\Candle;
use App\Entity\StrategyResult;
use App\Entity\TrendLine;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\MakerBundle\Str;

class Strategies
{

    const STRATEGY_LIST = ["rsiAndBollinger", "rsiAndMacd", "supportAndResistance"];

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

    public function rsiAndBollinger() : StrategyResult
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

        if($rsi < 30) {
            $rsiResult = StrategyResult::TRADE_LONG;
        } else if($rsi > 70) {
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

    public function rsiAndMacd() : StrategyResult
    {
        $macd = $this->indicators->macd($this->data);
        $rsi = $this->indicators->rsi($this->data);

        $result = new StrategyResult();

        if($rsi < 30 && $macd > 0) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($rsi > 70 && $macd < 0) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        }

        return $result;
    }

    /**
     * @param $tradePrice
     * @param $percentage
     * @return StrategyResult
     */
    public function stopLosses($tradePrice, $percentage)
    {
        $result = new StrategyResult();

        $stopLossPrice = $tradePrice * (1-$percentage);
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

        $takeProfitPrice = $tradePrice * (1+$percentage);
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

        if($breakoutsResult->getTradeResult() != StrategyResult::NO_TRADE) {
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
        /** TODO add volume and possibly earlier price movement to confirm breakout */
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