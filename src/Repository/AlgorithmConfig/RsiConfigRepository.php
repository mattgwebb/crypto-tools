<?php

namespace App\Repository\AlgorithmConfig;

use App\Entity\AlgorithmConfig\RsiConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method RsiConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method RsiConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method RsiConfig[]    findAll()
 * @method RsiConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RsiConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RsiConfig::class);
    }
}
