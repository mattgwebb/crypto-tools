<?php

namespace App\Repository;

use App\Entity\Data\ExternalIndicatorDataType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ExternalIndicatorDataType|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExternalIndicatorDataType|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalIndicatorDataType[]    findAll()
 * @method ExternalIndicatorDataType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalIndicatorDataTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalIndicatorDataType::class);
    }

}
