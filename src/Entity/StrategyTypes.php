<?php


namespace App\Entity;


abstract class StrategyTypes
{
    const RSI_BOLLINGER = "rsiAndBollinger";
    const RSI_MACD = "rsiAndMacd";
    const SUPPORT_RESISTANCE = "supportAndResistance";
    const RSI_DIVERGENCE = "rsiDivergence";
    const OBI_DIVERGENCE = "obvDivergence";
    const EMA_SCALP = "emaScalp";
    const EMA_CROSSOVER = "emaCrossover";
}