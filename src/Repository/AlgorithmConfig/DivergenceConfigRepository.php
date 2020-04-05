<?php

namespace App\Repository\AlgorithmConfig;

use App\Entity\AlgorithmConfig\DivergenceConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method DivergenceConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method DivergenceConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method DivergenceConfig[]    findAll()
 * @method DivergenceConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DivergenceConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DivergenceConfig::class);
    }
}
