<?php

namespace App\Repository\Data;

use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method CurrencyPair|null find($id, $lockMode = null, $lockVersion = null)
 * @method CurrencyPair|null findOneBy(array $criteria, array $orderBy = null)
 * @method CurrencyPair[]    findAll()
 * @method CurrencyPair[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyPairRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrencyPair::class);
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param $timeFrame
     * @param int $fromTime
     * @param int $toTime
     * @return Candle[]
     */
    public function getCandlesByTimeFrame(CurrencyPair $currencyPair, $timeFrame, $fromTime = 0, $toTime = 0)
    {
        $timeFrameSeconds = $timeFrame * 60;

        /** @var CandleRepository $candleRepo */
        $candleRepo = $this->getEntityManager()->getRepository(Candle::class);
        return $candleRepo->getByCurrencyFromTimeFrame($currencyPair, $timeFrameSeconds, $fromTime, $toTime);
    }
}
