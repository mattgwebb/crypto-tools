<?php


namespace App\Entity;


abstract class TradeTypes
{
    const TRADE_BUY = 1;
    const TRADE_SELL = 2;

    const LIMIT = 1;
    const MARKET = 2;
    const STOP_LOSS = 3;
    const STOP_LOSS_LIMIT = 4;
    const TAKE_PROFIT = 5;
    const TAKE_PROFIT_LIMIT = 6;
    const LIMIT_MAKER = 7;
}