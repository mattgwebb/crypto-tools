<?php

namespace App\Repository\Algorithm;

use App\Entity\Algorithm\AlgorithmStrategy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AlgorithmStrategy|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlgorithmStrategy|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlgorithmStrategy[]    findAll()
 * @method AlgorithmStrategy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlgorithmStrategyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlgorithmStrategy::class);
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
