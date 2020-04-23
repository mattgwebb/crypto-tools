<?php

namespace App\Repository\AlgorithmConfig;

use App\Entity\AlgorithmConfig\MovingAverageCrossoverConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MovingAverageCrossoverConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovingAverageCrossoverConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovingAverageCrossoverConfig[]    findAll()
 * @method MovingAverageCrossoverConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovingAverageCrossoverConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovingAverageCrossoverConfig::class);
    }
}
