<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CandleRepository")
 */
class Candle
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency", inversedBy="candles")
     */
    private $currency;

    /**
     * @ORM\Column(type="integer")
     */
    private $openTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $closeTime;

    /**
     * @ORM\Column(type="float")
     */
    private $openPrice;

    /**
     * @ORM\Column(type="float")
     */
    private $closePrice;

    /**
     * @ORM\Column(type="float")
     */
    private $highPrice;

    /**
     * @ORM\Column(type="float")
     */
    private $lowPrice;

    /**
     * @ORM\Column(type="float")
     */
    private $volume;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getOpenTime()
    {
        return $this->openTime;
    }

    /**
     * @param mixed $openTime
     */
    public function setOpenTime($openTime): void
    {
        $this->openTime = $openTime;
    }

    /**
     * @return mixed
     */
    public function getCloseTime()
    {
        return $this->closeTime;
    }

    /**
     * @param mixed $closeTime
     */
    public function setCloseTime($closeTime): void
    {
        $this->closeTime = $closeTime;
    }

    /**
     * @return mixed
     */
    public function getOpenPrice()
    {
        return $this->openPrice;
    }

    /**
     * @param mixed $openPrice
     */
    public function setOpenPrice($openPrice): void
    {
        $this->openPrice = $openPrice;
    }

    /**
     * @return mixed
     */
    public function getClosePrice()
    {
        return $this->closePrice;
    }

    /**
     * @param mixed $closePrice
     */
    public function setClosePrice($closePrice): void
    {
        $this->closePrice = $closePrice;
    }

    /**
     * @return mixed
     */
    public function getHighPrice()
    {
        return $this->highPrice;
    }

    /**
     * @param mixed $highPrice
     */
    public function setHighPrice($highPrice): void
    {
        $this->highPrice = $highPrice;
    }

    /**
     * @return mixed
     */
    public function getLowPrice()
    {
        return $this->lowPrice;
    }

    /**
     * @param mixed $lowPrice
     */
    public function setLowPrice($lowPrice): void
    {
        $this->lowPrice = $lowPrice;
    }

    /**
     * @return mixed
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * @param mixed $volume
     */
    public function setVolume($volume): void
    {
        $this->volume = $volume;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency): void
    {
        $this->currency = $currency;
    }
}
