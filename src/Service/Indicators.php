<?php

namespace App\Service;

use App\Entity\Candle;
use App\Entity\IndicatorResult;

/**
 * Class Indicators
 * @package App\Service
 *
 *          signal functions should return 1 for buy -1 for sell and 0 for no change
 *          other functions can return single floats for predictions.
 *
 *          all signal functions can be called alone, with just a pair or a just a pair and data
 *          time periods can be tweaked in backtesting and regression testing.
 *
 *          TODO: port over 'Ichimoku Kinko Hyo' (Ichimoku Cloud) for basic signals
 *          http://www.babypips.com/school/elementary/common-chart-indicators/summary-common-chart-indicators.html
 *          http://stockcharts.com/school/doku.php?id=chart_school:trading_strategies:ichimoku_cloud
 *          http://jsfiddle.net/oscglezm/phq7yo9y/
 *
 *          Types of indicators:
 *          overlap studies: BBANDS,DEMA,EMA,HT_TRENDLINE,KAMA,MA,MAMA,MAVP,MIDPOINT,MIDPRICE,SAR,SAREXT,SMA,T3,TEMA,TRIMA,WMA
 *          momentum indicators: ADX,ADXR,APO,AROON,AROONOSC,BOP,CCI,CMO,DX,MACD,MACDEXT,MACDFIX,MFI,MINUS_DI,MINUS_DM,
 *                               MOM,PLUS_DI,PLUS_DM,PPO,ROC,ROCP,ROCR,ROCR100,RSI,STOCH,STOCHF,STOCHRSI,TRIX,ULTOSC,WILLR
 *          volume indicators: AD,ADOSC,OBV
 *          volatility indicators: ATR,NATR,TRANGE
 *          cycle indicators: HT_DCPERIOD,HT_DCPHASE,HT_PHASOR,HT_SINE,HT_TRENDMODE
 */
class Indicators
{

    const RSI_TYPE = 'rsi';
    const BOLLINGER_TYPE = 'bollinger';
    const MACD_TYPE = 'macd';

    /**
     * @param string $pair
     * @param null   $data
     * @param int    $period
     *
     * @return IndicatorResult
     *
     * This algorithm uses the talib Bollinger Bands function to determine entry entry
     * points for long and sell/short positions.
     *
     * When the price breaks out of the upper Bollinger band, a sell or short position
     * is opened. A long position is opened when the price dips below the lower band.
     *
     *
     * Used to measure the market’s volatility.
     * They act like mini support and resistance levels.
     * Bollinger Bounce
     *
     * A strategy that relies on the notion that price tends to always return to the middle of the Bollinger bands.
     * You buy when the price hits the lower Bollinger band.
     * You sell when the price hits the upper Bollinger band.
     * Best used in ranging markets.
     * Bollinger Squeeze
     *
     * A strategy that is used to catch breakouts early.
     * When the Bollinger bands “squeeze”, it means that the market is very quiet, and a breakout is eminent.
     * Once a breakout occurs, we enter a trade on whatever side the price makes its breakout.
     */

    public function bollingerBands($data, $period=20, $devup=2, $devdn=2)
    {
        $data2 = $data;
        #$prev_close = array_pop($data2['close']); #[count($data['close']) - 2]; // prior close
        $current = array_pop($data2['close']); #[count($data['close']) - 1];    // we assume this is current

        # array $real [, integer $timePeriod [, float $nbDevUp [, float $nbDevDn [, integer $mAType ]]]]
        $bbands = trader_bbands($data['close'], $period, $devup, $devdn, 0);
        $upper  = $bbands[0];
        #$middle = $bbands[1]; // we'll find a use for you, one day
        $lower  = $bbands[2];

        $lowerLastBand = array_pop($lower);
        $higherLastBand = array_pop($upper);

        $result = new IndicatorResult(self::BOLLINGER_TYPE);
        $result->setExtraData(["lowerBand" => $lowerLastBand, "higherBand" => $higherLastBand, "current" => $current]);

        # If price is below the recent lower band
        if ($current <= $lowerLastBand) {
            $result->setTradeResult(IndicatorResult::TRADE_LONG); // buy long
            # If price is above the recent upper band
        } elseif ($current >= $higherLastBand) {
            $result->setTradeResult(IndicatorResult::TRADE_SHORT); // sell (or short)
        } else {
            $result->setTradeResult(IndicatorResult::NO_TRADE); // no trade
        }
        return $result;
    }

    /**
     * @param string $pair
     * @param null   $data
     * @param int    $period
     *
     * @return IndicatorResult
     * Relative Strength Index indicator as a buy/sell signal.
     *
     * Similar to the stochastic in that it indicates overbought and oversold conditions.
     * When RSI is above 70, it means that the market is overbought and we should look to sell.
     * When RSI is below 30, it means that the market is oversold and we should look to buy.
     * RSI can also be used to confirm trend formations. If you think a trend is forming, wait for
     * RSI to go above or below 50 (depending on if you’re looking at an uptrend or downtrend) before you enter a trade.
     */
    public function rsi($data, $period=14)
    {
        $LOW_RSI  = 30;
        $HIGH_RSI = 70;

        #$data2 = $data;
        #$current = array_pop($data2['close']); #$data['close'][count($data['close']) - 1];    // we assume this is current
        #$prev_close = array_pop($data2['close']); #$data['close'][count($data['close']) - 2]; // prior close

        $rsi = trader_rsi ($data['close'], $period);
        $rsi = array_pop($rsi);

        $result = new IndicatorResult(self::RSI_TYPE);
        $result->setValue($rsi);

        # RSI is above 70 and we own, sell
        if ($rsi > $HIGH_RSI) {
            $result->setTradeResult(IndicatorResult::TRADE_SHORT);
            # RSI is below 30, buy
        } elseif ($rsi < $LOW_RSI) {
            $result->setTradeResult(IndicatorResult::TRADE_LONG);
        } else {
            $result->setTradeResult(IndicatorResult::NO_TRADE);
        }
        return $result;
    }

    /**
     * @param string $pair
     * @param null   $data
     * @param int    $period
     *
     * @return IndicatorResult
     *
     * Moving Average Crossover Divergence (MACD) indicator as a buy/sell signal.
     * When the MACD signal less than 0, the price is trending down and it's time to sell.
     * When the MACD signal greater than 0, the price is trending up it's time to buy.
     *
     * Used to catch trends early and can also help us spot trend reversals.
     * It consists of 2 moving averages (1 fast, 1 slow) and vertical lines called a histogram,
     * which measures the distance between the 2 moving averages.
     * Contrary to what many people think, the moving average lines are NOT moving averages of the price.
     * They are moving averages of other moving averages.
     * MACD’s downfall is its lag because it uses so many moving averages.
     * One way to use MACD is to wait for the fast line to “cross over” or “cross under” the slow line and
     * enter the trade accordingly because it signals a new trend.
     */
    public function macd($data, $period1=12, $period2=26, $period3=9)
    {
        # Create the MACD signal and pass in the three parameters: fast period, slow period, and the signal.
        # we will want to tweak these periods later for now these are fine.
        #  data, fast period, slow period, signal period (2-100000)

        # array $real [, integer $fastPeriod [, integer $slowPeriod [, integer $signalPeriod ]]]
        $macd = trader_macd($data['close'], $period1, $period2, $period3);
        $macd_raw = $macd[0];
        $signal   = $macd[1];
        $hist     = $macd[2];

        $result = new IndicatorResult(self::MACD_TYPE);

        //If not enough Elements for the Function to complete
        if(!$macd || !$macd_raw){
            $result->setTradeResult(IndicatorResult::NO_TRADE);
            return $result;
        }

        #$macd = $macd_raw[count($macd_raw)-1] - $signal[count($signal)-1];
        $macd = (array_pop($macd_raw) - array_pop($signal));

        $result->setValue($macd);

        # Close position for the pair when the MACD signal is negative
        if ($macd < 0) {
            $result->setTradeResult(IndicatorResult::TRADE_SHORT);
            # Enter the position for the pair when the MACD signal is positive
        } elseif ($macd > 0) {
            $result->setTradeResult(IndicatorResult::TRADE_LONG);
        } else {
            $result->setTradeResult(IndicatorResult::NO_TRADE);
        }
        return $result;
    }

    /**
     * @param Candle[] $candles
     * @return array
     */
    public function prepareData($candles)
    {
        $data = [];
        /** @var Candle $candle */
        foreach($candles as $candle) {
            $data['open'][] = $candle->getOpenPrice();
            $data['close'][] = $candle->getClosePrice();
        }
        return $data;
    }
}