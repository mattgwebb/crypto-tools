<?php

namespace App\Repository;

use App\Entity\BotAlgorithm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method BotAlgorithm|null find($id, $lockMode = null, $lockVersion = null)
 * @method BotAlgorithm|null findOneBy(array $criteria, array $orderBy = null)
 * @method BotAlgorithm[]    findAll()
 * @method BotAlgorithm[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BotAlgorithmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotAlgorithm::class);
    }

    // /**
    //  * @return BotAlgorithm[] Returns an array of BotAlgorithm objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BotAlgorithm
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
