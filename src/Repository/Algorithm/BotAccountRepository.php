<?php

namespace App\Repository\Algorithm;

use App\Entity\Algorithm\BotAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method BotAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method BotAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method BotAccount[]    findAll()
 * @method BotAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BotAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotAccount::class);
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
