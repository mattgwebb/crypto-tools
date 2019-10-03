<?php


namespace App\Service;


use App\Entity\Exchange;

class ApiFactory
{
    /**
     * @param Exchange $exchange
     * @return ApiInterface|bool
     */
    public static function getApi(Exchange $exchange)
    {
        if($exchange->getName() == 'binance') {
            return new BinanceAPI();
        } else if($exchange->getName() == 'kraken') {
            return new KrakenAPI();
        } else return false;
    }
}