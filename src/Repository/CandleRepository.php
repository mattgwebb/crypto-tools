<?php

namespace App\Repository;

use App\Entity\Data\Candle;
use App\Entity\Data\Currency;
use App\Entity\Data\CurrencyPair;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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
}
