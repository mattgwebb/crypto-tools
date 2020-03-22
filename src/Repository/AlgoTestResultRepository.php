<?php

namespace App\Repository;

use App\Entity\AlgoTestResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AlgoTestResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlgoTestResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlgoTestResult[]    findAll()
 * @method AlgoTestResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlgoTestResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlgoTestResult::class);
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
