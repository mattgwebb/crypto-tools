<?php

namespace App\Repository;

use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\TimeFrames;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method CurrencyPair|null find($id, $lockMode = null, $lockVersion = null)
 * @method CurrencyPair|null findOneBy(array $criteria, array $orderBy = null)
 * @method CurrencyPair[]    findAll()
 * @method CurrencyPair[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyPairRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrencyPair::class);
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param $timeFrame
     * @param int $fromTime
     * @param int $toTime
     * @return Candle[]
     * TODO change to query to not have to load all candles
     */
    public function getCandlesByTimeFrame(CurrencyPair $currencyPair, $timeFrame, $fromTime = 0, $toTime = 0)
    {
        //$groupAmount = $timeFrame / TimeFrames::TIMEFRAME_5M;
        $timeFrameSeconds = $timeFrame * 60;
        $candles = [];
        $i = 1;
        $aux = new Candle();

        /** @var CandleRepository $candleRepo */
        $candleRepo = $this->getEntityManager()->getRepository(Candle::class);
        $allCandles = $candleRepo->getByCurrencyFromTime($currencyPair, $fromTime, $toTime);

        if($timeFrame == TimeFrames::TIMEFRAME_5M) {
            return $allCandles;
        }

        /** @var Candle $candle */
        foreach($allCandles as $candle) {
            if($i == 1) {
                $aux = $this->copyCandleData($candle);
            } else {
                if($candle->getHighPrice() > $aux->getHighPrice()) {
                    $aux->setHighPrice($candle->getHighPrice());
                }

                if($candle->getLowPrice() < $aux->getLowPrice()) {
                    $aux->setLowPrice($candle->getLowPrice());
                }

                $aux->addToVolume($candle->getVolume());

                if($candle->getCloseTime() % $timeFrameSeconds == ($timeFrameSeconds-1)) {
                    $aux->setCloseTime($candle->getCloseTime());
                    $aux->setClosePrice($candle->getClosePrice());
                    $candles[] = $aux;
                    $i = 0;
                }
            }
            $i++;
        }
        return $candles;
    }

    /**
     * @param Candle $candle
     * @return Candle
     */
    private function copyCandleData(Candle $candle)
    {
        $newCandle = new Candle();
        $newCandle->setClosePrice($candle->getClosePrice());
        $newCandle->setCloseTime($candle->getCloseTime());
        $newCandle->setOpenPrice($candle->getOpenPrice());
        $newCandle->setOpenTime($candle->getOpenTime());

        $newCandle->setHighPrice($candle->getHighPrice());
        $newCandle->setLowPrice($candle->getLowPrice());

        $newCandle->setVolume($candle->getVolume());

        $newCandle->setCurrencyPair($candle->getCurrencyPair());

        return $newCandle;
    }
}
