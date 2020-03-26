<?php


namespace App\Service\Exchanges;


use App\Entity\Data\Exchange;

class ApiFactory
{
    /**
     * TODO return static instance from container, not new instance every time
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