<?php

namespace App\Entity\AlgorithmConfig;

use App\Entity\Algorithm\BotAlgorithm;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AlgorithmConfig\MovingAverageConfigRepository")
 * @ORM\Table(name="algo_ma_config")
 */
class MovingAverageConfig
{
    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm", inversedBy="maConfig")
     * @var BotAlgorithm
     */
    private $algo;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $period;

    /**
     * @return BotAlgorithm
     */
    public function getAlgo(): BotAlgorithm
    {
        return $this->algo;
    }

    /**
     * @param BotAlgorithm $algo
     */
    public function setAlgo(BotAlgorithm $algo): void
    {
        $this->algo = $algo;
    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @param int $period
     */
    public function setPeriod(int $period): void
    {
        $this->period = $period;
    }
}
