<?php

namespace App\Repository\Algorithm;

use App\Entity\Algorithm\AlgoTestResult;
use App\Entity\Algorithm\BotAlgorithm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AlgoTestResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlgoTestResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlgoTestResult[]    findAll()
 * @method AlgoTestResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlgoTestResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlgoTestResult::class);
    }

    /**
     * @param AlgoTestResult $testResult
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function newAlgoTestResult(AlgoTestResult $testResult)
    {
        $data = $testResult->jsonSerialize();

        $this->getEntityManager()->getConnection()->insert('algo_test_result', $data);

        return $data;
    }
}
