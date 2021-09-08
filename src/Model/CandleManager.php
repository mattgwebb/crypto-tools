<?php


namespace App\Model;


use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Repository\Data\CandleRepository;
use App\Repository\Data\CurrencyPairRepository;

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
     * @return Candle[]
     */
    public function getCandlesByTimeFrame(BotAlgorithm $algo, int $loadFrom, int $lastOpen)
    {
        return $this->currencyPairRepo
            ->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame(), $loadFrom, $lastOpen);
    }

    /**
     * @param int $id
     * @return Candle|null
     */
    public function getCandle(int $id)
    {
        return $this->candleRepo->find($id);
    }

    /**
     * @param CurrencyPair $pair
     * @return Candle|null
     */
    public function getLatestCandle(CurrencyPair $pair)
    {
        return $this->candleRepo->findLast($pair);
    }
}