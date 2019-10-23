<?php

namespace App\Repository;

use App\Entity\Candle;
use App\Entity\Currency;
use App\Entity\CurrencyPair;
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
     * @param $from
     * @return Candle[] Returns an array of Candle objects
     */

    public function getByCurrencyFromTime(CurrencyPair $currencyPair, $from)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.currency = :currency')
            ->andWhere('c.openTime >= :time')
            ->setParameter('currencyPair', $currencyPair)
            ->setParameter('time', $from)
            ->orderBy('c.openTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
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
