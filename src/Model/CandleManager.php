<?php


namespace App\Model;


use App\Entity\BotAlgorithm;
use App\Repository\CurrencyPairRepository;

class CandleManager
{

    /**
     * @var CurrencyPairRepository
     */
    private $currencyPairRepo;

    /**
     * CandleManager constructor.
     * @param CurrencyPairRepository $currencyPairRepo
     */
    public function __construct(CurrencyPairRepository $currencyPairRepo)
    {
        $this->currencyPairRepo = $currencyPairRepo;
    }


    /**
     * @param BotAlgorithm $algo
     * @param int $loadFrom
     * @param int $lastOpen
     * @return \App\Entity\Candle[]
     */
    public function getCandlesByTimeFrame(BotAlgorithm $algo, int $loadFrom, int $lastOpen)
    {
        return $this->currencyPairRepo
            ->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame(), $loadFrom, $lastOpen);
    }

    /**
     * @param int $id
     * @return \App\Entity\CurrencyPair|null
     */
    public function getCandle(int $id)
    {
        return $this->currencyPairRepo->find($id);
    }
}