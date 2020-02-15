<?php


namespace App\Model;


use App\Entity\BotAlgorithm;
use App\Repository\CandleRepository;
use App\Repository\CurrencyPairRepository;

class CandleManager
{

    /**
     * @var CurrencyPairRepository
     */
    private $currencyPairRepo;

    /**
     * @var CandleRepository
     */
    private $candleRepo;

    /**
     * CandleManager constructor.
     * @param CurrencyPairRepository $currencyPairRepo
     * @param CandleRepository $candleRepo
     */
    public function __construct(CurrencyPairRepository $currencyPairRepo, CandleRepository $candleRepo)
    {
        $this->currencyPairRepo = $currencyPairRepo;
        $this->candleRepo = $candleRepo;
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
     * @return \App\Entity\Candle|null
     */
    public function getCandle(int $id)
    {
        return $this->candleRepo->find($id);
    }
}