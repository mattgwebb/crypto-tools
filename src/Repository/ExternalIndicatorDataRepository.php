<?php

namespace App\Repository;

use App\Entity\Data\ExternalIndicatorData;
use App\Entity\Data\ExternalIndicatorDataType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ExternalIndicatorData|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExternalIndicatorData|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalIndicatorData[]    findAll()
 * @method ExternalIndicatorData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalIndicatorDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalIndicatorData::class);
    }

    /**
     * @param ExternalIndicatorDataType $indicatorDataType
     * @return ExternalIndicatorData|null
     */
    public function getLatestData(ExternalIndicatorDataType $indicatorDataType)
    {
        return $this->findOneBy(["indicatorType" => $indicatorDataType], ["closeTime" => "desc"]);
    }

}
