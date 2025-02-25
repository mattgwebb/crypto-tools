<?php

namespace App\Service\TechnicalAnalysis;

use App\Entity\Data\Candle;


class Indicators
{

    const FIB_RETRACEMENT_LEVELS = [0.786, 0.618, 0.500, 0.382, 0.236];

    /**
     * @param array $data
     * @param bool $prior
     * @param int $period
     * @param int $devup
     * @param int $devdn
     *
     * @return array
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

    public function bollingerBands($data, $prior = false, $period=20, $devup=2, $devdn=2)
    {
        # array $real [, integer $timePeriod [, float $nbDevUp [, float $nbDevDn [, integer $mAType ]]]]
        $bbands = trader_bbands($data['close'], $period, $devup, $devdn, 0);
        $upper  = $bbands[0];
        #$middle = $bbands[1]; // we'll find a use for you, one day
        $lower  = $bbands[2];

        $lowerLastBand = array_pop($lower);
        $higherLastBand = array_pop($upper);

        $lowerPriorBand = array_pop($lower);
        $higherPriorBand = array_pop($upper);

        return ($prior ? [$higherPriorBand, $lowerPriorBand] : [$higherLastBand, $lowerLastBand]);
    }

    /**
     * @param $data
     * @param int $period
     * @param int $devup
     * @param int $devdn
     * @return array
     */
    public function bollingerBandsPeriod($data, $period=20, $devup=2, $devdn=2)
    {
        return trader_bbands($data['close'], $period, $devup, $devdn, 0);
    }

    public function keltnerChannelPeriod($data, $period=20, $devup=2, $devdn=2)
    {
        $emaPeriod = trader_ema($data['close'], $period);
        $atrPeriod = trader_atr($data['high'], $data['low'], $data['close'], 10);

        $channel = [];

        foreach($emaPeriod as $key => $ema) {

            if(!isset($atrPeriod[$key])) {
                continue;
            }

            $channel[0][$key] = $ema + ($atrPeriod[$key] * $devup);
            $channel[1][$key] = $ema;
            $channel[2][$key] = $ema - ($atrPeriod[$key] * $devdn);
        }
        return $channel;
    }

    /**
     * @param array $data
     * @param int $period
     *
     * @param bool $prior
     * @return float
     * Relative Strength Index indicator as a buy/sell signal.
     *
     * Similar to the stochastic in that it indicates overbought and oversold conditions.
     * When RSI is above 70, it means that the market is overbought and we should look to sell.
     * When RSI is below 30, it means that the market is oversold and we should look to buy.
     * RSI can also be used to confirm trend formations. If you think a trend is forming, wait for
     * RSI to go above or below 50 (depending on if you’re looking at an uptrend or downtrend) before you enter a trade.
     */
    public function rsi($data, $period=14, $prior=false)
    {
        $rsiArray = trader_rsi ($data['close'], $period);
        $rsi = @array_pop($rsiArray) ?? 0;
        $rsi_prior = @array_pop($rsiArray) ?? 0;
        return ($prior ? $rsi_prior : $rsi);
    }

    /**
     * @param $data
     * @param int $period
     * @param bool $prior
     * @return float
     */
    public function mfi($data, $period=14, $prior=false)
    {
        $mfiArray = trader_mfi ($data['high'], $data['low'], $data['close'], $data['volume'], $period);
        $mfi = @array_pop($mfiArray) ?? 0;
        $mfi_prior = @array_pop($mfiArray) ?? 0;
        return ($prior ? $mfi_prior : $mfi);
    }

    /**
     * @param $data
     * @param int $period
     * @return mixed
     */
    public function mfiPeriod($data, $period=14)
    {
        $mfi = trader_mfi ($data['high'], $data['low'], $data['close'], $data['volume'], $period);
        return $mfi;
    }

    /**
     * @param $data
     * @param int $period
     * @return mixed
     */
    public function rsiPeriod($data, $period=14)
    {
        $rsi = trader_rsi ($data['close'], $period);
        return $rsi;
    }

    /**
     * @param array $data
     * @param int $period1
     * @param int $period2
     * @param int $period3
     *
     * @return float
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


        //If not enough Elements for the Function to complete
        if(!$macd || !$macd_raw){
            return 0;
        }

        #$macd = $macd_raw[count($macd_raw)-1] - $signal[count($signal)-1];
        $macd = (array_pop($macd_raw) - array_pop($signal));
        return $macd;
    }

    /**
     * @param $data
     * @param $period
     * @param bool $prior
     * @return float
     */
    public function ema($data, $period, $prior=false)
    {
        $emaArray = trader_ema($data, $period);
        $ema = @array_pop($emaArray) ?? 0;
        $ema_prior = @array_pop($emaArray) ?? 0;
        return ($prior ? $ema_prior : $ema);
    }

    /**
     * @param $data
     * @param $period
     * @param bool $prior
     * @return float
     */
    public function ma($data, $period, $prior=false)
    {
        $maArray = trader_ma($data, $period);
        $ma = @array_pop($maArray) ?? 0;
        $ma_prior = @array_pop($maArray) ?? 0;
        return ($prior ? $ma_prior : $ma);
    }

    /**
     * @param $data
     * @return array
     */
    public function obvPeriod($data)
    {
        $obv = trader_obv ($data['close'], $data['volume']);
        return $obv;
    }

    /**
     * Returns K values
     * @param $data
     * @param int $fastKPeriod
     * @param int $slowKPeriod
     * @param bool $prior
     * @return float
     */
    public function stoch($data, int $fastKPeriod = 14, int $slowKPeriod = 3, $prior = false)
    {
        $stochArray = trader_stoch($data['high'], $data['low'], $data['close'], $fastKPeriod, $slowKPeriod);
        $stoch = array_pop($stochArray[0]);
        $stoch_prior = array_pop($stochArray[0]);
        return ($prior ? $stoch_prior : $stoch);
    }

    /**
     * Returns K values
     * @param $data
     * @param int $fastKPeriod
     * @param int $slowKPeriod
     * @return array
     */
    public function stochPeriod($data, int $fastKPeriod = 14, int $slowKPeriod = 3)
    {
        $stochArray = trader_stoch($data['high'], $data['low'], $data['close'], $fastKPeriod, $slowKPeriod);
        return $stochArray[0];
    }

    /**
     * @param $data
     * @param int $fastPeriod
     * @param int $slowPeriod
     * @return float
     */
    public function chaikinOscillatorPeriod($data, int $fastPeriod = 3, int $slowPeriod = 10)
    {
        $chaikin = trader_adosc($data['high'], $data['low'], $data['close'], $data['volume'], $fastPeriod, $slowPeriod);
        return $chaikin;
    }

    /**
     * @param $data
     * @param int $period
     * @return float
     */
    public function adx($data, int $period = 14)
    {
        $adx = trader_adx($data['high'], $data['low'], $data['close'], $period);
        $adx = @array_pop($adx) ?? 0;
        return $adx;
    }

    /**
     * @param $data
     * @param int $period
     * @return float
     */
    public function mom($data, int $period = 14)
    {
        $mom  = trader_mom($data['close'], $period);
        $mom  = @array_pop($mom) ?? 0;
        return $mom;
    }

    /**
     * @param $data
     * @param float $acceleration
     * @param float $maximum
     *
     * @return int
     *
     *  This is a forex version of SAR which is used with Stoch.
     *  The idea is the positioning of the sar is above 'certain' kinds of candles
     */
    public function fsar($data, $acceleration=0.02, $maximum=0.02)
    {
        # array $high , array $low [, float $acceleration [, float $maximum ]]
        $_sar = trader_sar($data['high'], $data['low'], $acceleration, $maximum);
        $current_sar = (float) array_pop($_sar);
        $prior_sar   = (float) array_pop($_sar);

        $last_high  = (float) array_pop($data['high']);
        $last_low   = (float) array_pop($data['low']);
        $last_open  = (float) array_pop($data['open']);
        $last_close = (float) array_pop($data['close']);

        $prior_high  = (float) array_pop($data['high']);
        $prior_low   = (float) array_pop($data['low']);
        $prior_open  = (float) array_pop($data['open']);
        $prior_close = (float) array_pop($data['close']);

        $prev_open  = (float) array_pop($data['open']);
        $prev_close = (float) array_pop($data['close']);

        $below        = $current_sar < $last_low;
        $above        = $current_sar > $last_high;
        $red_candle   = $last_open < $last_close;
        $green_candle = $last_open > $last_close;

        $prior_below        = $prior_sar < $prior_low;
        $prior_above        = $prior_sar > $prior_high;
        $prior_red_candle   = $prior_open < $prior_close;
        $prior_green_candle = $prior_open > $prior_close;

        $prev_red_candle   = $prev_open < $prev_close;
        $prev_green_candle = $prev_open > $prev_close;

        $prior_red_candle   = ($prev_red_candle || $prior_red_candle ? true : false);
        $prior_green_candle = ($prev_green_candle || $prior_green_candle ? true : false);


        if (($prior_above && $prior_red_candle) && ($below && $green_candle)) {
            /** SAR is below a NEW green candle. */
            return 1; // buy signal
        } elseif (($prior_below && $prior_green_candle) && ($above && $red_candle)) {
            /** SAR is above a NEW red candle */
            return -1; // sell signal
        } else {
            /** do nothing  */
            return 0; // twiddle thumbs
        }
    }

    /**
     * @param $data
     * @param $period
     * @return array
     */
    public function adxPeriod($data, int $period = 14)
    {
        return trader_adx($data['high'], $data['low'], $data['close'], $period);
    }

    /**
     * TODO implement (doesn´t calculate D+ D- lines, needed for crossover)
     * @param $data
     * @param $period
     * @param bool $prior
     * @return array
     */
    public function dmiPeriod($data, $period, $prior = false)
    {
        $dmiArray = trader_dx($data['high'], $data['low'], $data['close'], $period);
        $dmi = @array_pop($dmiArray) ?? 0;
        $dmi_prior = @array_pop($dmiArray) ?? 0;
        return ($prior ? $dmi : $dmi_prior);
    }

    /**
     * @param $data
     * @param int $smallPeriod
     * @param int $mediumPeriod
     * @param int $longPeriod
     * @param int $laggingPeriod
     * @return array
     */
    public function ichimokuCloud($data, int $smallPeriod = 9, int $mediumPeriod = 26, int $longPeriod = 52, int $laggingPeriod = 26)
    {
        $smallPeriodHighs = array_slice($data['high'], $smallPeriod * (-1));
        $smallPeriodLows = array_slice($data['low'], $smallPeriod * (-1));

        $mediumPeriodHighs = array_slice($data['high'], $mediumPeriod * (-1));
        $mediumPeriodLows = array_slice($data['low'], $mediumPeriod * (-1));

        $longPeriodHighs = array_slice($data['high'], $longPeriod * (-1));
        $longPeriodLows = array_slice($data['low'], $longPeriod * (-1));

        $conversionLine = (array_sum($smallPeriodHighs) + array_sum($smallPeriodLows)) / 2;

        $baseLine = (array_sum($mediumPeriodHighs) + array_sum($mediumPeriodLows)) / 2;

        $leadingSpanA = ($conversionLine + $baseLine) / 2;

        $leadingSpanB = (array_sum($longPeriodHighs) + array_sum($longPeriodLows)) / 2;

        $laggingSpan = $data['close'][count($data['close']) - $laggingPeriod];

        return array($conversionLine, $baseLine, $leadingSpanA, $leadingSpanB, $laggingSpan);
    }

    /**
     * @param $data
     * @param int $period
     * @return float
     */
    public function volumeIncreaseAverage($data, int $period = 10)
    {
        $periodVolume = array_slice($data['volume'], $period * (-1));
        $averageVolume = array_sum($periodVolume) / 10;

        $currentVolume = $periodVolume[$period - 1];

        return (($currentVolume / $averageVolume) - 1) * 100;
    }

    /**
     * @param $data
     * @param int $period
     * @return array
     */
    public function priceRangePeriod($data, int $period = 50)
    {
        $periodCloses = array_slice($data['close'], $period * (-1));
        return [min($periodCloses), max($periodCloses)];
    }

    /**
     * @param array $candles
     * @return array
     */
    public function prepareDataFromCandles(array $candles)
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
        return $data;
    }
}