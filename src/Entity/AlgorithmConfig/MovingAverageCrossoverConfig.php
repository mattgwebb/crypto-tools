<?php

namespace App\Entity\AlgorithmConfig;

use App\Entity\Algorithm\BotAlgorithm;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AlgorithmConfig\MovingAverageCrossoverConfigRepository")
 * @ORM\Table(name="algo_ma_crossover_config")
 */
class MovingAverageCrossoverConfig
{

    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm", inversedBy="maCrossoverConfig")
     * @var BotAlgorithm
     */
    private $algo;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $smallPeriod;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $longPeriod;

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
    public function getSmallPeriod(): int
    {
        return $this->smallPeriod;
    }

    /**
     * @param int $smallPeriod
     */
    public function setSmallPeriod(int $smallPeriod): void
    {
        $this->smallPeriod = $smallPeriod;
    }

    /**
     * @return int
     */
    public function getLongPeriod(): int
    {
        return $this->longPeriod;
    }

    /**
     * @param int $longPeriod
     */
    public function setLongPeriod(int $longPeriod): void
    {
        $this->longPeriod = $longPeriod;
    }
}
