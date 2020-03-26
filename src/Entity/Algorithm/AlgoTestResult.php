<?php

namespace App\Entity\Algorithm;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AlgoTestResultRepository")
 */
class AlgoTestResult
{

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm")
     * @var BotAlgorithm
     */
    private $algo;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $timeFrame;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $timestamp;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $startTime;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $endTime;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $percentage;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $observations = '';

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $trades;

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
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return int
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     */
    public function setStartTime(int $startTime): void
    {
        $this->startTime = $startTime;
    }

    /**
     * @return int
     */
    public function getEndTime(): int
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     */
    public function setEndTime(int $endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * @return float
     */
    public function getPercentage(): float
    {
        return $this->percentage;
    }

    /**
     * @param float $percentage
     */
    public function setPercentage(float $percentage): void
    {
        $this->percentage = $percentage;
    }

    /**
     * @return string
     */
    public function getObservations(): string
    {
        return $this->observations;
    }

    /**
     * @param string $observations
     */
    public function setObservations(string $observations): void
    {
        $this->observations = $observations;
    }

    /**
     * @return int
     */
    public function getTrades(): int
    {
        return $this->trades;
    }

    /**
     * @param int $trades
     */
    public function setTrades(int $trades): void
    {
        $this->trades = $trades;
    }

    /**
     * @return int
     */
    public function getTimeFrame(): int
    {
        return $this->timeFrame;
    }

    /**
     * @param int $timeFrame
     */
    public function setTimeFrame(int $timeFrame): void
    {
        $this->timeFrame = $timeFrame;
    }
}
