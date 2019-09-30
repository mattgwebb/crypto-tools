<?php

namespace App\Repository;

use App\Entity\Candle;
use App\Entity\Currency;
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

    // /**
    //  * @return Candle[] Returns an array of Candle objects
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

    /**
     * @param Currency $currency
     * @return Candle|null
     */
    public function findLast(Currency $currency): ?Candle
    {
        return $this->findOneBy(["currency" => $currency], ["openTime" => "desc"]);
    }
}
