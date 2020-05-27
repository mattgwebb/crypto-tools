<?php


namespace App\Service\Algorithm;


use App\Entity\Algorithm\BotAccount;
use App\Repository\Algorithm\BotAccountRepository;
use Doctrine\ORM\EntityManagerInterface;

class BotAccountService
{

    /**
     * @var BotAccountRepository
     */
    private $repo;

    /**
     * BotAccountService constructor.
     * @param BotAccountRepository $repo
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(BotAccountRepository $repo, EntityManagerInterface $entityManager)
    {
        $this->repo = $repo;
    }

    /**
     * @param int $id
     * @return BotAccount
     */
    public function getBotAccount(int $id)
    {
        return $this->repo->find($id);
    }
}