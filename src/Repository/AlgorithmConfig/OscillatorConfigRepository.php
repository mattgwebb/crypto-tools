<?php

namespace App\Repository\AlgorithmConfig;

use App\Entity\AlgorithmConfig\OscillatorConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method OscillatorConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method OscillatorConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method OscillatorConfig[]    findAll()
 * @method OscillatorConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OscillatorConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OscillatorConfig::class);
    }
}
