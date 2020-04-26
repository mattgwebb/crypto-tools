<?php

namespace App\Entity\AlgorithmConfig;

use App\Entity\Algorithm\BotAlgorithm;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AlgorithmConfig\OscillatorConfigRepository")
 * @ORM\Table(name="algo_oscillator_config")
 */
class OscillatorConfig
{

    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm", inversedBy="rsiConfig")
     * @var BotAlgorithm
     */
    private $algo;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $buyUnder;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $sellOver;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $calculationPeriod;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $crossOnly;

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
     * @return float
     */
    public function getBuyUnder(): float
    {
        return $this->buyUnder;
    }

    /**
     * @param float $buyUnder
     */
    public function setBuyUnder(float $buyUnder): void
    {
        $this->buyUnder = $buyUnder;
    }

    /**
     * @return float
     */
    public function getSellOver(): float
    {
        return $this->sellOver;
    }

    /**
     * @param float $sellOver
     */
    public function setSellOver(float $sellOver): void
    {
        $this->sellOver = $sellOver;
    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->calculationPeriod;
    }

    /**
     * @param int $period
     */
    public function setPeriod(int $period): void
    {
        $this->calculationPeriod = $period;
    }

    /**
     * @return bool
     */
    public function isCrossOnly(): bool
    {
        return $this->crossOnly;
    }

    /**
     * @param bool $crossOnly
     */
    public function setCrossOnly(bool $crossOnly): void
    {
        $this->crossOnly = $crossOnly;
    }
}
