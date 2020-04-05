<?php

namespace App\Repository\AlgorithmConfig;

use App\Entity\AlgorithmConfig\AdaptivePQConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AdaptivePQConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdaptivePQConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdaptivePQConfig[]    findAll()
 * @method AdaptivePQConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdaptivePQRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdaptivePQConfig::class);
    }
}
