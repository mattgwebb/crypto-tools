<?php

namespace App\Entity\Algorithm;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Algorithm\AlgoTestResultRepository")
 */
class AlgoTestResult implements \JsonSerializable
{

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    private $testRun;

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
    private $tradeCount;

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
    private $bestWinner = 0.0;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $worstLoser = 0.0;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $averageWinner = 0.0;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $averageLoser = 0.0;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $winPercentage = 0.0;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $standardDeviation = 0.0;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $openPosition = 0.0;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $testType = TestTypes::STANDARD_TEST;

    /**
     * @var array
     * @ORM\Column(type="json")
     */
    private $equityCurve = null;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $maxDrawdown = 0.0;

    /**
     * @var array
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
    public function getTradeCount(): int
    {
        return $this->tradeCount;
    }

    /**
     * @param int $trades
     */
    public function setTradeCount(int $trades): void
    {
        $this->tradeCount = $trades;
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

    /**
     * @return float
     */
    public function getOpenPosition(): float
    {
        return $this->openPosition;
    }

    /**
     * @param float $openPosition
     */
    public function setOpenPosition(float $openPosition): void
    {
        $this->openPosition = $openPosition;
    }

    /**
     * @return int
     */
    public function getTestType(): int
    {
        return $this->testType;
    }

    /**
     * @param int $testType
     */
    public function setTestType(int $testType): void
    {
        $this->testType = $testType;
    }

    /**
     * @return array
     */
    public function getTrades(): array
    {
        return $this->trades;
    }

    /**
     * @param array $trades
     */
    public function setTrades(array $trades): void
    {
        $this->trades = $trades;
    }

    /**
     * @return array
     */
    public function getEquityCurve(): ?array
    {
        return $this->equityCurve;
    }

    /**
     * @param array $equityCurve
     */
    public function setEquityCurve(?array $equityCurve): void
    {
        $this->equityCurve = $equityCurve;
    }

    /**
     * @return float
     */
    public function getMaxDrawdown(): float
    {
        return $this->maxDrawdown;
    }

    /**
     * @param float $maxDrawdown
     */
    public function setMaxDrawdown(float $maxDrawdown): void
    {
        $this->maxDrawdown = $maxDrawdown;
    }

    /**
     * @return int|null
     */
    public function getTestRun()
    {
        return $this->testRun;
    }

    /**
     * @param int $testRun
     */
    public function setTestRun(int $testRun): void
    {
        $this->testRun = $testRun;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'algo_id' => $this->getAlgo()->getId(),
            'currency_pair_id' => $this->getAlgo()->getCurrencyPair()->getId(),
            'time_frame' => $this->getAlgo()->getTimeFrame(),
            'timestamp' => time(),
            'start_time' => $this->getStartTime(),
            'end_time' => $this->getEndTime(),
            'percentage' => $this->getPercentage(),
            'percentage_with_fees' => $this->getPercentageWithFees(),
            'price_change_percentage' => $this->getPriceChangePercentage(),
            'observations' => $this->getObservations(),
            'trade_count' => $this->getTradeCount(),
            'invalidated_trades' => $this->getInvalidatedTrades(),
            'best_winner' => $this->getBestWinner() ? $this->getBestWinner() : 0,
            'worst_loser' => $this->getWorstLoser() ? $this->getWorstLoser() : 0,
            'average_winner' => $this->getAverageWinner() ? $this->getAverageWinner() : 0,
            'average_loser' => $this->getAverageLoser() ? $this->getAverageLoser() : 0,
            'win_percentage' => $this->getWinPercentage() ? $this->getWinPercentage() : 0,
            'standard_deviation' => $this->getStandardDeviation() ? $this->getStandardDeviation() : 0,
            'open_position' => $this->getOpenPosition(),
            'test_type' => $this->getTestType(),
            'equity_curve' => json_encode($this->getEquityCurve()),
            'max_drawdown' => $this->getMaxDrawdown(),
            'test_run' => $this->getTestRun()
        ];
    }
}
