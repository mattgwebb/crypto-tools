<?php


namespace App\Service\Algorithm;


use App\Entity\Algorithm\BotAccount;
use App\Entity\Trade\BotAccountHistoricalPortfolio;
use App\Repository\Algorithm\BotAccountRepository;
use Doctrine\ORM\EntityManagerInterface;

class BotAccountService
{

    /**
     * @var BotAccountRepository
     */
    private $repo;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * BotAccountService constructor.
     * @param BotAccountRepository $repo
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(BotAccountRepository $repo, EntityManagerInterface $entityManager)
    {
        $this->repo = $repo;
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $id
     * @return BotAccount
     */
    public function getBotAccount(int $id)
    {
        return $this->repo->find($id);
    }

    /**
     * @return BotAccount[]
     */
    public function getAllBotAccounts()
    {
        return $this->repo->findAll();
    }

    /**
     * @param BotAccountHistoricalPortfolio $botAccountHistoricalPortfolio
     */
    public function saveHistoricalPortfolioValue(BotAccountHistoricalPortfolio $botAccountHistoricalPortfolio)
    {
        $this->entityManager->persist($botAccountHistoricalPortfolio);
        $this->entityManager->flush();
    }
}