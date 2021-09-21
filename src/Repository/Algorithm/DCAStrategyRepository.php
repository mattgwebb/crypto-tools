<?php

namespace App\Repository\Algorithm;

use App\Entity\Algorithm\DCAStrategy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method DCAStrategy|null find($id, $lockMode = null, $lockVersion = null)
 * @method DCAStrategy|null findOneBy(array $criteria, array $orderBy = null)
 * @method DCAStrategy[]    findAll()
 * @method DCAStrategy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DCAStrategyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DCAStrategy::class);
    }
}
