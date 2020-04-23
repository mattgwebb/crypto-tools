<?php

namespace App\Repository\AlgorithmConfig;

use App\Entity\AlgorithmConfig\MovingAverageConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MovingAverageConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovingAverageConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovingAverageConfig[]    findAll()
 * @method MovingAverageConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovingAverageConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovingAverageConfig::class);
    }
}
