<?php

namespace App\Repository\AlgorithmConfig;

use App\Entity\AlgorithmConfig\EmaCrossoverConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method EmaCrossoverConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmaCrossoverConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmaCrossoverConfig[]    findAll()
 * @method EmaCrossoverConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmaCrossoverConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmaCrossoverConfig::class);
    }
}
