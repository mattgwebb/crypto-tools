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
}
