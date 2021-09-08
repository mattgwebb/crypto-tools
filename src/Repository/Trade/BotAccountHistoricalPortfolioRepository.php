<?php

namespace App\Repository\Trade;

use App\Entity\Trade\BotAccountHistoricalPortfolio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method BotAccountHistoricalPortfolio|null find($id, $lockMode = null, $lockVersion = null)
 * @method BotAccountHistoricalPortfolio|null findOneBy(array $criteria, array $orderBy = null)
 * @method BotAccountHistoricalPortfolio[]    findAll()
 * @method BotAccountHistoricalPortfolio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BotAccountHistoricalPortfolioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotAccountHistoricalPortfolio::class);
    }

}
