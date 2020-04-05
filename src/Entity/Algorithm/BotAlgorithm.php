<?php

namespace App\Entity\Algorithm;

use App\Entity\AlgorithmConfig\AdaptivePQConfig;
use App\Entity\AlgorithmConfig\DivergenceConfig;
use App\Entity\AlgorithmConfig\MovingAverageCrossoverConfig;
use App\Entity\AlgorithmConfig\RsiConfig;
use App\Entity\Trade\TradeTypes;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BotAlgorithmRepository")
 */
class BotAlgorithm implements \JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Data\CurrencyPair", inversedBy="algos")
     */
    private $currencyPair;

    /**
     * @ORM\Column(type="integer")
     */
    private $timeFrame;

    /**
     * @ORM\Column(type="string")
     */
    private $strategy;

    /**
     * @ORM\Column(type="integer")
     */
    private $stopLoss;

    /**
     * @ORM\Column(type="integer")
     */
    private $takeProfit;

    /**
     * @ORM\Column(type="text")
     */
    private $observations;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="smallint")
     */
    private $tradeStatus = TradeTypes::TRADE_SELL;

    /**
     * @ORM\Column(type="smallint")
     */
    private $mode = AlgoModes::NOT_ACTIVE;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AlgorithmConfig\MovingAverageCrossoverConfig", mappedBy="algo")
     * @var MovingAverageCrossoverConfig
     */
    private $maCrossoverConfig;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AlgorithmConfig\RsiConfig", mappedBy="algo")
     * @var RsiConfig
     */
    private $rsiConfig;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AlgorithmConfig\DivergenceConfig", mappedBy="algo")
     * @var DivergenceConfig
     */
    private $divergenceConfig;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AlgorithmConfig\AdaptivePQConfig", mappedBy="algo")
     * @var AdaptivePQConfig
     */
    private $adaptivePQConfig;

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
     * @return mixed
     */
    public function getTimeFrame()
    {
        return $this->timeFrame;
    }

    /**
     * @param mixed $timeFrame
     */
    public function setTimeFrame($timeFrame): void
    {
        $this->timeFrame = $timeFrame;
    }

    /**
     * @return mixed
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @param mixed $strategy
     */
    public function setStrategy($strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * @return mixed
     */
    public function getStopLoss()
    {
        return $this->stopLoss;
    }

    /**
     * @param mixed $stopLoss
     */
    public function setStopLoss($stopLoss): void
    {
        $this->stopLoss = $stopLoss;
    }

    /**
     * @return mixed
     */
    public function getTakeProfit()
    {
        return $this->takeProfit;
    }

    /**
     * @param mixed $takeProfit
     */
    public function setTakeProfit($takeProfit): void
    {
        $this->takeProfit = $takeProfit;
    }

    /**
     * @return mixed
     */
    public function getObservations()
    {
        return $this->observations;
    }

    /**
     * @return bool
     */
    public function isLong()
    {
        return $this->tradeStatus == TradeTypes::TRADE_BUY;
    }

    /**
     * @return bool
     */
    public function isShort()
    {
        return $this->tradeStatus == TradeTypes::TRADE_SELL;
    }

    public function setLong()
    {
        $this->tradeStatus = TradeTypes::TRADE_BUY;
    }

    public function setShort()
    {
        $this->tradeStatus = TradeTypes::TRADE_SELL;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @param mixed $observations
     */
    public function setObservations($observations): void
    {
        $this->observations = $observations;
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return MovingAverageCrossoverConfig
     */
    public function getMaCrossoverConfig(): MovingAverageCrossoverConfig
    {
        return $this->maCrossoverConfig;
    }

    /**
     * @param MovingAverageCrossoverConfig $maCrossoverConfig
     */
    public function setMaCrossoverConfig(MovingAverageCrossoverConfig $maCrossoverConfig): void
    {
        $this->maCrossoverConfig = $maCrossoverConfig;
    }

    /**
     * @return RsiConfig
     */
    public function getRsiConfig(): RsiConfig
    {
        return $this->rsiConfig;
    }

    /**
     * @param RsiConfig $rsiConfig
     */
    public function setRsiConfig(RsiConfig $rsiConfig): void
    {
        $this->rsiConfig = $rsiConfig;
    }

    /**
     * @return DivergenceConfig
     */
    public function getDivergenceConfig(): DivergenceConfig
    {
        return $this->divergenceConfig;
    }

    /**
     * @param DivergenceConfig $divergenceConfig
     */
    public function setDivergenceConfig(DivergenceConfig $divergenceConfig): void
    {
        $this->divergenceConfig = $divergenceConfig;
    }

    /**
     * @return AdaptivePQConfig
     */
    public function getAdaptivePQConfig(): AdaptivePQConfig
    {
        return $this->adaptivePQConfig;
    }

    /**
     * @param AdaptivePQConfig $adaptivePQConfig
     */
    public function setAdaptivePQConfig(AdaptivePQConfig $adaptivePQConfig): void
    {
        $this->adaptivePQConfig = $adaptivePQConfig;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            "id" => $this->getId(),
            "symbol" => $this->getCurrencyPair()->getSymbol(),
            "time_frame" => $this->getTimeFrame(),
            "strategy" => $this->getStrategy(),
            "stop_loss" => $this->getStopLoss(),
            "take_profit" => $this->getTakeProfit()
        ];
    }
}
