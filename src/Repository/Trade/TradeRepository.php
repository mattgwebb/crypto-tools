<?php

namespace App\Repository\Trade;

use App\Entity\Algorithm\BotAccount;
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
     * @param BotAccount $account
     * @return Trade|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getBotAccountLastTrade(BotAccount $account)
    {
        return $this->createQueryBuilder('c')
            ->where('c.botAccount = :account')
            ->setParameter('account', $account)
            ->orderBy('c.timeStamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
