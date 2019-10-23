<?php

namespace App\Repository;

use App\Entity\Candle;
use App\Entity\CurrencyPair;
use App\Entity\TimeFrames;
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
     * @param $fromTime
     * @return Candle[]
     * TODO change to query to not have to load all candles
     */
    public function getCandlesByTimeFrame(CurrencyPair $currencyPair, $timeFrame, $fromTime)
    {
        $groupAmount = $timeFrame / TimeFrames::TIMEFRAME_5M;
        $candles = [];
        $i = 1;
        $aux = new Candle();

        /** @var CandleRepository $candleRepo */
        $candleRepo = $this->getEntityManager()->getRepository(Candle::class);
        $allCandles = $candleRepo->getByCurrencyFromTime($currencyPair, $fromTime);

        /** @var Candle $candle */
        foreach($allCandles as $candle) {
            if($i == 1) {
                $aux = $candle;
            } else {
                if($candle->getHighPrice() > $aux->getHighPrice()) {
                    $aux->setHighPrice($candle->getHighPrice());
                }

                if($candle->getLowPrice() < $aux->getLowPrice()) {
                    $aux->setLowPrice($candle->getLowPrice());
                }

                $aux->addToVolume($candle->getVolume());

                if($i == $groupAmount) {
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
}
