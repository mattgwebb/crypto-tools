<?php

namespace App\Entity\Algorithm;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Algorithm\AlgoTestResultRepository")
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
     * @ORM\Column(type="float")
     * @var float
     */
    private $percentageWithFees;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $priceChangePercentage;

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
     * @ORM\Column(type="integer")
     * @var int
     */
    private $invalidatedTrades;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Data\CurrencyPair")
     */
    private $currencyPair;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $bestWinner;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $worstLoser;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $averageWinner;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $averageLoser;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $winPercentage;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $standardDeviation;

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

    /**
     * @return int
     */
    public function getInvalidatedTrades(): int
    {
        return $this->invalidatedTrades;
    }

    /**
     * @param int $invalidatedTrades
     */
    public function setInvalidatedTrades(int $invalidatedTrades): void
    {
        $this->invalidatedTrades = $invalidatedTrades;
    }

    /**
     * @return mixed
     */
    public function getCurrencyPair()
    {
        return $this->currencyPair;
    }

    /**
     * @param mixed $currencyPair
     */
    public function setCurrencyPair($currencyPair): void
    {
        $this->currencyPair = $currencyPair;
    }

    /**
     * @return float
     */
    public function getPriceChangePercentage(): float
    {
        return $this->priceChangePercentage;
    }

    /**
     * @param float $priceChangePercentage
     */
    public function setPriceChangePercentage(float $priceChangePercentage): void
    {
        $this->priceChangePercentage = $priceChangePercentage;
    }

    /**
     * @return float
     */
    public function getPercentageWithFees(): float
    {
        return $this->percentageWithFees;
    }

    /**
     * @param float $percentageWithFees
     */
    public function setPercentageWithFees(float $percentageWithFees): void
    {
        $this->percentageWithFees = $percentageWithFees;
    }

    /**
     * @return float
     */
    public function getBestWinner(): float
    {
        return $this->bestWinner;
    }

    /**
     * @param float $bestWinner
     */
    public function setBestWinner(float $bestWinner): void
    {
        $this->bestWinner = $bestWinner;
    }

    /**
     * @return float
     */
    public function getWorstLoser(): float
    {
        return $this->worstLoser;
    }

    /**
     * @param float $worstLoser
     */
    public function setWorstLoser(float $worstLoser): void
    {
        $this->worstLoser = $worstLoser;
    }

    /**
     * @return float
     */
    public function getAverageWinner(): float
    {
        return $this->averageWinner;
    }

    /**
     * @param float $averageWinner
     */
    public function setAverageWinner(float $averageWinner): void
    {
        $this->averageWinner = $averageWinner;
    }

    /**
     * @return float
     */
    public function getAverageLoser(): float
    {
        return $this->averageLoser;
    }

    /**
     * @param float $averageLoser
     */
    public function setAverageLoser(float $averageLoser): void
    {
        $this->averageLoser = $averageLoser;
    }

    /**
     * @return float
     */
    public function getWinPercentage(): float
    {
        return $this->winPercentage;
    }

    /**
     * @param float $winPercentage
     */
    public function setWinPercentage(float $winPercentage): void
    {
        $this->winPercentage = $winPercentage;
    }

    /**
     * @return float
     */
    public function getStandardDeviation(): float
    {
        return $this->standardDeviation;
    }

    /**
     * @param float $standardDeviation
     */
    public function setStandardDeviation(float $standardDeviation): void
    {
        $this->standardDeviation = $standardDeviation;
    }
}
