<?php


namespace App\Entity;


abstract class TimeFrames
{
    /**
     * In minutes
     */
    const TIMEFRAME_5M = 5;
    const TIMEFRAME_15M = 15;
    const TIMEFRAME_30M = 30;
    const TIMEFRAME_45M = 45;
    const TIMEFRAME_1H = 60;
    const TIMEFRAME_2H = 120;
    const TIMEFRAME_3H = 180;
    const TIMEFRAME_4H = 240;
    const TIMEFRAME_1D = 1440;
    const TIMEFRAME_1W = 10080;
}