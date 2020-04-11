<?php

namespace App\Entity\AlgorithmConfig;

use App\Entity\Algorithm\BotAlgorithm;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AlgorithmConfig\DivergenceConfigRepository")
 * @ORM\Table(name="algo_divergence_config")
 */
class DivergenceConfig
{

    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm", inversedBy="divergenceConfig")
     * @var BotAlgorithm
     */
    private $algo;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $lastCandles;

    /**
     * Minumum candle difference for line divergences (so we don´t draw lines between two adjacent points)
     * @ORM\Column(type="integer")
     * @var int
     */
    private $minCandleDifference;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $minDivergencePercentage;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $regularDivergences;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $hiddenDivergences;

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
    public function getLastCandles(): int
    {
        return $this->lastCandles;
    }

    /**
     * @param int $lastCandles
     */
    public function setLastCandles(int $lastCandles): void
    {
        $this->lastCandles = $lastCandles;
    }

    /**
     * @return int
     */
    public function getMinCandleDifference(): int
    {
        return $this->minCandleDifference;
    }

    /**
     * @param int $minCandleDifference
     */
    public function setMinCandleDifference(int $minCandleDifference): void
    {
        $this->minCandleDifference = $minCandleDifference;
    }

    /**
     * @return int
     */
    public function getMinDivergencePercentage(): int
    {
        return $this->minDivergencePercentage;
    }

    /**
     * @param int $minDivergencePercentage
     */
    public function setMinDivergencePercentage(int $minDivergencePercentage): void
    {
        $this->minDivergencePercentage = $minDivergencePercentage;
    }

    /**
     * @return bool
     */
    public function isRegularDivergences(): bool
    {
        return $this->regularDivergences;
    }

    /**
     * @param bool $regularDivergences
     */
    public function setRegularDivergences(bool $regularDivergences): void
    {
        $this->regularDivergences = $regularDivergences;
    }

    /**
     * @return bool
     */
    public function isHiddenDivergences(): bool
    {
        return $this->hiddenDivergences;
    }

    /**
     * @param bool $hiddenDivergences
     */
    public function setHiddenDivergences(bool $hiddenDivergences): void
    {
        $this->hiddenDivergences = $hiddenDivergences;
    }
}
