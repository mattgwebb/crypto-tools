<?php


namespace App\Entity;


abstract class TradeStatusTypes
{
    const NEW = 1;
    const PARTIALLY_FILLED = 2;
    const FILLED = 3;
    const CANCELED = 4;
    const PENDING_CANCEL = 5;
    const REJECTED = 6;
    const EXPIRED = 7;
}