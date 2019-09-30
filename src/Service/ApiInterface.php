<?php


namespace App\Service;


use App\Entity\Currency;

interface ApiInterface
{
    public function getAPIBaseRoute() : string;

    public function getCandles(Currency $currency, $timeFrame, $startTime);
}