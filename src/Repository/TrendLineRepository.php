<?php

namespace App\Repository;

use App\Entity\TechnicalAnalysis\TrendLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TrendLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method TrendLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method TrendLine[]    findAll()
 * @method TrendLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrendLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrendLine::class);
    }

    // /**
    //  * @return TrendLine[] Returns an array of TrendLine objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TrendLine
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
