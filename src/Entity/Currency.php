<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CurrencyRepository")
 */
class Currency
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $symbol;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Candle", mappedBy="currency")
     */
    private $candles;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TrendLine", mappedBy="currency")
     */
    private $trendLines;

    /**
     * @ORM\Column(type="float")
     */
    private $balance;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Exchange", inversedBy="currencies")
     */
    private $exchange;

    public function __construct()
    {
        $this->candles = new ArrayCollection();
    }

    /**
     * @return Collection|Candle[]
     */
    public function getCandles(): Collection
    {
        return $this->candles;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @param mixed $symbol
     */
    public function setSymbol($symbol): void
    {
        $this->symbol = $symbol;
    }

    /**
     * @return mixed
     */
    public function getTrendLines()
    {
        return $this->trendLines;
    }

    /**
     * @param mixed $trendLines
     */
    public function setTrendLines($trendLines): void
    {
        $this->trendLines = $trendLines;
    }

    /**
     * @return mixed
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param mixed $exchange
     */
    public function setExchange($exchange): void
    {
        $this->exchange = $exchange;
    }

    public function __toString()
    {
        return $this->getId().".".$this->getSymbol();
    }

    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param mixed $balance
     */
    public function setBalance($balance): void
    {
        $this->balance = $balance;
    }
}
