<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BotAlgorithmRepository")
 */
class BotAlgorithm
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
     * @ORM\ManyToOne(targetEntity="App\Entity\CurrencyPair")
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
     * @param mixed $observations
     */
    public function setObservations($observations): void
    {
        $this->observations = $observations;
    }
}
