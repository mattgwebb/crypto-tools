<?php

namespace App\Entity\AlgorithmConfig;

use App\Entity\BotAlgorithm;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AlgorithmConfig\RsiConfigRepository")
 * @ORM\Table(name="algo_rsi_config")
 */
class RsiConfig
{

    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\BotAlgorithm", inversedBy="rsiConfig")
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
}
