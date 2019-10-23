<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CurrencyPairRepository")
 */
class CurrencyPair
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency", inversedBy="pairs")
     */
    private $firstCurrency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     */
    private $secondCurrency;


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
    public function getFirstCurrency()
    {
        return $this->firstCurrency;
    }

    /**
     * @param mixed $firstCurrency
     */
    public function setFirstCurrency($firstCurrency): void
    {
        $this->firstCurrency = $firstCurrency;
    }

    /**
     * @return mixed
     */
    public function getSecondCurrency()
    {
        return $this->secondCurrency;
    }

    /**
     * @param mixed $secondCurrency
     */
    public function setSecondCurrency($secondCurrency): void
    {
        $this->secondCurrency = $secondCurrency;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getId().".".$this->getSymbol();
    }
}
