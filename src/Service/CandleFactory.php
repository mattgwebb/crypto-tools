<?php


namespace App\Service;


use App\Entity\Candle;

class CandleFactory
{

    /**
     * @param $rawCandle
     * @param $currency
     * @return Candle
     */
    public static function createFromBinance($rawCandle, $currency)
    {
        $candle = new Candle();
        $candle->setOpenTime((int)($rawCandle[0]/1000));
        $candle->setOpenPrice($rawCandle[1]);
        $candle->setHighPrice($rawCandle[2]);
        $candle->setLowPrice($rawCandle[3]);
        $candle->setClosePrice($rawCandle[4]);
        $candle->setVolume($rawCandle[5]);
        $candle->setCloseTime((int)($rawCandle[6]/1000));
        $candle->setCurrency($currency);
        return $candle;
    }
}