<?php

namespace App\Repository;

use App\Entity\Candle;
use App\Entity\Currency;
use App\Entity\TimeFrames;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Currency|null find($id, $lockMode = null, $lockVersion = null)
 * @method Currency|null findOneBy(array $criteria, array $orderBy = null)
 * @method Currency[]    findAll()
 * @method Currency[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    // /**
    //  * @return Currency[] Returns an array of Currency objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Currency
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @param $timeFrame
     * @return Candle[]
     * TODO change to query to not have to load all candles
     */
    public function getCandlesByTimeFrame(Currency $currency, $timeFrame, $fromTime)
    {
        $groupAmount = $timeFrame / TimeFrames::TIMEFRAME_5M;
        $candles = [];
        $i = 1;
        $aux = new Candle();

        /** @var CandleRepository $candleRepo */
        $candleRepo = $this->getEntityManager()->getRepository(Candle::class);
        $allCandles = $candleRepo->getByCurrencyFromTime($currency, $fromTime);

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
