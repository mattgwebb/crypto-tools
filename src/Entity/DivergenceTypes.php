<?php


namespace App\Entity;


abstract class DivergenceTypes
{
    const NO_DIVERGENCE = 0;
    const BULLISH_REGULAR_DIVERGENCE = 1;
    const BULLISH_HIDDEN_DIVERGENCE = 2;
    const BEARISH_REGULAR_DIVERGENCE = 3;
    const BEARISH_HIDDEN_DIVERGENCE = 4;
}