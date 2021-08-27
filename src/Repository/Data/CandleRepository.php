<?php

namespace App\Repository\Data;

use App\Entity\Data\Candle;
use App\Entity\Data\Currency;
use App\Entity\Data\CurrencyPair;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method Candle|null find($id, $lockMode = null, $lockVersion = null)
 * @method Candle|null findOneBy(array $criteria, array $orderBy = null)
 * @method Candle[]    findAll()
 * @method Candle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CandleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candle::class);
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $from
     * @param int $to
     * @return Candle[] Returns an array of Candle objects
     */

    public function getByCurrencyFromTime(CurrencyPair $currencyPair, int $from, int $to)
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->andWhere('c.currencyPair = :currencyPair')
            ->andWhere('c.openTime >= :from')
            ->setParameter('currencyPair', $currencyPair)
            ->setParameter('from', $from)
            ->orderBy('c.openTime', 'ASC');

        if($to > 0) {
            $queryBuilder
                ->andWhere('c.openTime < :to')
                ->setParameter('to', $to);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * @param CurrencyPair $currencyPair
     * @param int $timeFrameSeconds
     * @param int $from
     * @param int $to
     * @return Candle[]
     */
    public function getByCurrencyFromTimeFrame(CurrencyPair $currencyPair, int $timeFrameSeconds, int $from, int $to)
    {
        $sql = "SELECT t1.id, t1.currency_pair_id, t1.open_time_first AS open_time, t1.close_time_last AS close_time, ".
                "t1.open_price_first AS open_price, t1.close_price_last AS close_price, t1.highest_price AS high_price, ".
                "t1.lowest_price AS low_price, SUM(volume) AS volume ".
                "FROM ( ".
                    "SELECT id, currency_pair_id, ".
                    "FIRST_VALUE(open_time) OVER (PARTITION BY n_day ORDER BY open_time ASC) AS open_time_first, ".
                    "FIRST_VALUE(close_time) OVER (PARTITION BY n_day ORDER BY open_time DESC) AS close_time_last, ".
                    "FIRST_VALUE(open_price) OVER (PARTITION BY n_day ORDER BY open_time ASC) AS open_price_first, ".
                    "FIRST_VALUE(close_price) OVER (PARTITION BY n_day ORDER BY open_time DESC) AS close_price_last, ".
                    "FIRST_VALUE(high_price) OVER (PARTITION BY n_day ORDER BY high_price DESC) AS highest_price, ".
                    "FIRST_VALUE(low_price) OVER (PARTITION BY n_day ORDER BY low_price ASC) AS lowest_price, ".
                    "volume, ".
                    "CEIL(close_time / ?) AS n_day FROM candle ".
                    "WHERE currency_pair_id = ? and open_time >= ?";
        if($to > 0) {
            $sql .= " and open_time < $to";
        }
        $sql .= ")".
                "AS t1 ".
                "GROUP BY n_day ".
                "ORDER BY n_day;";

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Candle::class, 'c');
        $rsm->addFieldResult('c', 'id', 'id');
        $rsm->addMetaResult('c', 'currency_pair_id', 'currency_pair_id');
        $rsm->addFieldResult('c', 'open_time', 'openTime');
        $rsm->addFieldResult('c', 'close_time', 'closeTime');
        $rsm->addFieldResult('c', 'open_price', 'openPrice');
        $rsm->addFieldResult('c', 'close_price', 'closePrice');
        $rsm->addFieldResult('c', 'high_price', 'highPrice');
        $rsm->addFieldResult('c', 'low_price', 'lowPrice');
        $rsm->addFieldResult('c', 'volume', 'volume');

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter(1, $timeFrameSeconds);
        $query->setParameter(2, $currencyPair->getId());
        $query->setParameter(3, $from);



        $candles = $query->getResult();

        if($candles) {
            /** @var Candle $lastCandle */
            $lastCandle = $candles[count($candles)-1];
            if($lastCandle->getCloseTime() % $timeFrameSeconds <> ($timeFrameSeconds-1)) {
                unset($candles[count($candles)-1]);
            }
        }
        return $candles;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $timestamp
     * @return Candle|null
     */
    public function getCandleByTime(CurrencyPair $currencyPair, int $timestamp)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.currencyPair = :currencyPair')
            ->andWhere('c.closeTime <= :timestamp')
            ->setParameter('currencyPair', $currencyPair)
            ->setParameter('timestamp', $timestamp)
            ->orderBy('c.closeTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $limit
     * @return Candle[] Returns an array of Candle objects
     */

    public function getByCurrencyLimit(CurrencyPair $currencyPair, int $limit)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.currencyPair = :currencyPair')
            ->setParameter('currencyPair', $currencyPair)
            ->orderBy('c.openTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return Candle|null
     */
    public function findLast(CurrencyPair $currencyPair): ?Candle
    {
        return $this->findOneBy(["currencyPair" => $currencyPair], ["openTime" => "desc"]);
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return Candle|null
     */
    public function findFirst(CurrencyPair $currencyPair): ?Candle
    {
        return $this->findOneBy(["currencyPair" => $currencyPair], ["openTime" => "asc"]);
    }

    /**
     * @param int $timestamp
     */
    public function deleteCandlesBeforeTimestamp(int $timestamp)
    {
        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.openTime < :timestamp')
            ->setParameter('timestamp', $timestamp)
            ->getQuery()
            ->execute();
    }
}
