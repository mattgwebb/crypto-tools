<?php


namespace App\Entity\Algorithm;


abstract class StrategyTypes
{
    const RSI = "rsi";
    const MA = "ma";
    const EMA = "ema";
    const BOLLINGER_BANDS = "bollingerBands";
    const MACD = "macd";
    const RSI_BOLLINGER = "rsiAndBollinger";
    const RSI_MACD = "rsiAndMacd";
    const MACD_BOLLINGER = "macdAndBollinger";
    const SUPPORT_RESISTANCE = "supportAndResistance";
    const RSI_DIVERGENCE = "rsiDivergence";
    const OBV_DIVERGENCE = "obvDivergence";
    const CHAIKIN_DIVERGENCE = "chaikinDivergence";
    const EMA_SCALP = "emaScalp";
    const EMA_CROSSOVER = "emaCrossover";
    const MA_CROSSOVER = "maCrossover";
    const ADAPTIVE_PQ = "adaptivePQ";
    const ADX_DMI = "adxDmi";
    const ADX_MOM = "adxMom";
    const STOCH = "stoch";
    const GUPPY_CROSSOVER = "guppyCrossover";

    const DIVERGENCE_STRATEGIES = [
        self::RSI_DIVERGENCE,
        self::OBV_DIVERGENCE,
        self::CHAIKIN_DIVERGENCE
    ];

    const OSCILLATOR_STRATEGIES = [
        self::RSI,
        self::STOCH
    ];

    const CROSSOVER_STRATEGIES = [
        self::MA_CROSSOVER,
        self::EMA_CROSSOVER
    ];

    const MOVING_AVERAGE_STRATEGIES = [
        self::MA,
        self::EMA
    ];
}