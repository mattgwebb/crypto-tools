<?php

namespace App\Entity\AlgorithmConfig;

use App\Entity\Algorithm\BotAlgorithm;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AlgorithmConfig\AdaptivePQRepository")
 * @ORM\Table(name="algo_adaptive_pq_config")
 */
class AdaptivePQConfig
{

    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm", inversedBy="adaptivePQConfig")
     * @var BotAlgorithm
     */
    private $algo;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $pValue;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $qValue;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $maIndicator;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $maPeriod;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $oscillatorIndicator;

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
    public function getPValue(): float
    {
        return $this->pValue;
    }

    /**
     * @param float $pValue
     */
    public function setPValue(float $pValue): void
    {
        $this->pValue = $pValue;
    }

    /**
     * @return float
     */
    public function getQValue(): float
    {
        return $this->qValue;
    }

    /**
     * @param float $qValue
     */
    public function setQValue(float $qValue): void
    {
        $this->qValue = $qValue;
    }

    /**
     * @return string
     */
    public function getMaIndicator(): string
    {
        return $this->maIndicator;
    }

    /**
     * @param string $maIndicator
     */
    public function setMaIndicator(string $maIndicator): void
    {
        $this->maIndicator = $maIndicator;
    }

    /**
     * @return int
     */
    public function getMaPeriod(): int
    {
        return $this->maPeriod;
    }

    /**
     * @param int $maPeriod
     */
    public function setMaPeriod(int $maPeriod): void
    {
        $this->maPeriod = $maPeriod;
    }

    /**
     * @return string
     */
    public function getOscillatorIndicator(): string
    {
        return $this->oscillatorIndicator;
    }

    /**
     * @param string $oscillatorIndicator
     */
    public function setOscillatorIndicator(string $oscillatorIndicator): void
    {
        $this->oscillatorIndicator = $oscillatorIndicator;
    }
}
