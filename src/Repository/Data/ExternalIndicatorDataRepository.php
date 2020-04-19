<?php

namespace App\Repository\Data;

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

    /**
     * @param ExternalIndicatorDataType $indicatorDataType
     * @param int $to
     * @param int $from
     * @return ExternalIndicatorData[]
     */
    public function getData(ExternalIndicatorDataType $indicatorDataType, int $to, int $from)
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->andWhere('c.indicatorType = :type')
            ->andWhere('c.closeTime >= :from')
            ->setParameter('type', $indicatorDataType)
            ->setParameter('from', $from)
            ->orderBy('c.closeTime', 'ASC');

        if($to > 0) {
            $queryBuilder
                ->andWhere('c.closeTime <= :to')
                ->setParameter('to', $to);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
