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
     * @param BotAlgorithm $algo
     * @param int $type
     * @param float $percentage
     * @param float $openPositionPercentage
     * @param float $percentageWithFees
     * @param float $periodPercentage
     * @param array $trades
     * @param int $invalidatedTrades
     * @param int $startTime
     * @param int $finishTime
     */
    public function newAlgoTestResult(BotAlgorithm $algo, int $type, float $percentage, float $openPositionPercentage, float $percentageWithFees, float $periodPercentage,
                                        array $trades, int $invalidatedTrades, int $startTime, int $finishTime)
    {
        $extra = [
            "entry_strategies" => $algo->getEntryStrategyCombination(),
            "market_conditions_entry_strategy" => $algo->getMarketConditionsEntry(),
            "exit_strategies" => $algo->getExitStrategyCombination(),
            "market_conditions_exit_strategy" => $algo->getMarketConditionsExit(),
            "invalidation_strategies" => $algo->getInvalidationStrategyCombination()
        ];

        $data = [
            'algo_id' => $algo->getId(),
            'currency_pair_id' => $algo->getCurrencyPair()->getId(),
            'time_frame' => $algo->getTimeFrame(),
            'timestamp' => time(),
            'start_time' => $startTime,
            'end_time' => $finishTime,
            'percentage' => $percentage,
            'percentage_with_fees' => $percentageWithFees,
            'price_change_percentage' => $periodPercentage,
            'observations' => json_encode($extra),
            'trades' => count($trades),
            'invalidated_trades' => $invalidatedTrades,
            'open_position' => $openPositionPercentage,
            'test_type' => $type
        ];

        if($trades) {
            $winningTrades = [];
            $losingTrades = [];

            foreach($trades as $trade) {
                if(isset($trade['percentage'])) {
                    if($trade['percentage'] > 0) {
                        $winningTrades[] = $trade['percentage'];
                    } else if($trade['percentage'] < 0) {
                        $losingTrades[] = $trade['percentage'];
                    }
                }
            }

            $nWinningTrades = count($winningTrades);
            $nLosingTrades = count($losingTrades);

            if($nWinningTrades > 0) {
                $data['best_winner'] = max($winningTrades);
                $data['average_winner'] = array_sum($winningTrades) / $nWinningTrades;
            } else {
                $data['best_winner'] = 0;
                $data['average_winner'] = 0;
            }

            if($nLosingTrades > 0) {
                $data['worst_loser'] = min($losingTrades);
                $data['average_loser'] = array_sum($losingTrades) / $nLosingTrades;
            } else {
                $data['worst_loser'] = 0;
                $data['average_loser'] = 0;
            }

            if(($nWinningTrades + $nLosingTrades) > 0) {
                $data['win_percentage'] = ($nWinningTrades / ($nWinningTrades + $nLosingTrades)) * 100;
            } else {
                $data['win_percentage'] = 0;
            }

            $data['standard_deviation'] = $this->calculateStandardDeviation(array_merge($winningTrades, $losingTrades));
        }

        $this->getEntityManager()->getConnection()->insert('algo_test_result', $data);
    }

    /**
     * @param array $a
     * @param bool $sample
     * @return float
     */
    private function calculateStandardDeviation(array $a, $sample = false)
    {
        $n = count($a);

        if($n == 0) {
            return 0.0;
        }

        $mean = array_sum($a) / $n;
        $carry = 0.0;

        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        }
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }
}
