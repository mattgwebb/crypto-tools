<?php

namespace App\Entity\AlgorithmConfig;

use App\Entity\BotAlgorithm;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AlgorithmConfig\EmaCrossoverConfigRepository")
 * @ORM\Table(name="algo_ema_crossover_config")
 */
class EmaCrossoverConfig
{

    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\BotAlgorithm", inversedBy="emaCrossoverConfig")
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
