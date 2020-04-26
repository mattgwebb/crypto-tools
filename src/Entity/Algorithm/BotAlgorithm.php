<?php

namespace App\Entity\Algorithm;

use App\Entity\AlgorithmConfig\AdaptivePQConfig;
use App\Entity\AlgorithmConfig\DivergenceConfig;
use App\Entity\AlgorithmConfig\MovingAverageConfig;
use App\Entity\AlgorithmConfig\MovingAverageCrossoverConfig;
use App\Entity\AlgorithmConfig\OscillatorConfig;
use App\Entity\Trade\TradeTypes;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Algorithm\BotAlgorithmRepository")
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
     * @ORM\OneToMany(targetEntity="App\Entity\Algorithm\AlgorithmStrategy", mappedBy="algo")
     */
    private $strategies;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AlgorithmConfig\MovingAverageCrossoverConfig", mappedBy="algo")
     * @var MovingAverageCrossoverConfig
     */
    private $maCrossoverConfig;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AlgorithmConfig\OscillatorConfig", mappedBy="algo")
     * @var OscillatorConfig
     */
    private $oscillatorConfig;

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
     * @ORM\OneToOne(targetEntity="App\Entity\AlgorithmConfig\MovingAverageConfig", mappedBy="algo")
     * @var MovingAverageConfig
     */
    private $maConfig;

    /**
     * BotAlgorithm constructor.
     */
    public function __construct()
    {
        $this->strategies = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getEntryStrategies()
    {
        $strategies = new ArrayCollection();

        /** @var AlgorithmStrategy $algoStrategy */
        foreach($this->strategies as $algoStrategy) {
            if($algoStrategy->isForEntry()) {
                $strategies->add($algoStrategy->getStrategy());
            }
        }
        return $strategies;
    }

    /**
     * @return ArrayCollection
     */
    public function getExitStrategies()
    {
        $strategies = new ArrayCollection();

        /** @var AlgorithmStrategy $algoStrategy */
        foreach($this->strategies as $algoStrategy) {
            if($algoStrategy->isForExit()) {
                $strategies->add($algoStrategy->getStrategy());
            }
        }
        return $strategies;
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
     * @return OscillatorConfig
     */
    public function getOscillatorConfig(): OscillatorConfig
    {
        return $this->oscillatorConfig;
    }

    /**
     * @param OscillatorConfig $oscillatorConfig
     */
    public function setOscillatorConfig(OscillatorConfig $oscillatorConfig): void
    {
        $this->oscillatorConfig = $oscillatorConfig;
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
     * @return MovingAverageConfig
     */
    public function getMaConfig(): MovingAverageConfig
    {
        return $this->maConfig;
    }

    /**
     * @param MovingAverageConfig $maConfig
     */
    public function setMaConfig(MovingAverageConfig $maConfig): void
    {
        $this->maConfig = $maConfig;
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
            "stop_loss" => $this->getStopLoss(),
            "take_profit" => $this->getTakeProfit()
        ];
    }
}
