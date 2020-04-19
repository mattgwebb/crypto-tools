<?php

namespace App\Repository\Trade;

use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Trade|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trade|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trade[]    findAll()
 * @method Trade[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trade::class);
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $tradeSide
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAlgoLastBuyTradePrice(BotAlgorithm $algo)
    {
        return $this->createQueryBuilder('c')
            ->select('c.price')
            ->where('c.algo = :algo')
            ->andWhere('c.type = :type')
            ->setParameter('algo', $algo)
            ->setParameter('type', TradeTypes::TRADE_BUY)
            ->orderBy('c.timeStamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
