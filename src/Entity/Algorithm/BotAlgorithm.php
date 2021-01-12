<?php

namespace App\Entity\Algorithm;

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
     * @ORM\Column(type="text")
     */
    private $observations;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $entryStrategyCombination;

    /**
     * @ORM\Column(type="text")
     */
    private $exitStrategyCombination;

    /**
     * @ORM\Column(type="text")
     */
    private $invalidationStrategyCombination;

    /**
     * @ORM\Column(type="text")
     */
    private $marketConditionsEntry;

    /**
     * @ORM\Column(type="text")
     */
    private $marketConditionsExit;

    /**
     * @ORM\Column(type="smallint")
     */
    private $testingPhase = TestingPhases::IMPLEMENTING;

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
    public function getObservations()
    {
        return $this->observations;
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
     * @return mixed
     */
    public function getEntryStrategyCombination()
    {
        return $this->entryStrategyCombination;
    }

    /**
     * @param mixed $entryStrategyCombination
     */
    public function setEntryStrategyCombination($entryStrategyCombination): void
    {
        $this->entryStrategyCombination = $entryStrategyCombination;
    }

    /**
     * @return mixed
     */
    public function getExitStrategyCombination()
    {
        return $this->exitStrategyCombination;
    }

    /**
     * @param mixed $exitStrategyCombination
     */
    public function setExitStrategyCombination($exitStrategyCombination): void
    {
        $this->exitStrategyCombination = $exitStrategyCombination;
    }

    /**
     * @return mixed
     */
    public function getInvalidationStrategyCombination()
    {
        return $this->invalidationStrategyCombination;
    }

    /**
     * @param mixed $invalidationStrategyCombination
     */
    public function setInvalidationStrategyCombination($invalidationStrategyCombination): void
    {
        $this->invalidationStrategyCombination = $invalidationStrategyCombination;
    }

    /**
     * @return mixed
     */
    public function getMarketConditionsEntry()
    {
        return $this->marketConditionsEntry;
    }

    /**
     * @param mixed $marketConditionsEntry
     */
    public function setMarketConditionsEntry($marketConditionsEntry): void
    {
        $this->marketConditionsEntry = $marketConditionsEntry;
    }

    /**
     * @return mixed
     */
    public function getMarketConditionsExit()
    {
        return $this->marketConditionsExit;
    }

    /**
     * @param mixed $marketConditionsExit
     */
    public function setMarketConditionsExit($marketConditionsExit): void
    {
        $this->marketConditionsExit = $marketConditionsExit;
    }

    /**
     * @return mixed
     */
    public function getTestingPhase()
    {
        return $this->testingPhase;
    }

    /**
     * @param mixed $testingPhase
     */
    public function setTestingPhase($testingPhase): void
    {
        $this->testingPhase = $testingPhase;
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
            "entry_strategies" => $this->getEntryStrategyCombination(),
            "market_conditions_entry_strategy" => $this->getMarketConditionsEntry(),
            "exit_strategies" => $this->getExitStrategyCombination(),
            "market_conditions_exit_strategy" => $this->getMarketConditionsExit(),
            "invalidation_strategies" => $this->getInvalidationStrategyCombination(),
            "time_frame" => $this->getTimeFrame()
        ];
    }
}
