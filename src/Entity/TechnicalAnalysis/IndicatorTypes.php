<?php


namespace App\Entity\TechnicalAnalysis;


abstract class IndicatorTypes
{
    const RSI = 'rsi';
    const EMA = 'ema';
    const CHAIKIN = 'chaikin';
    const OBV = 'obv';
    const MFI = 'mfi';
    const VOLUME = 'volume';
}