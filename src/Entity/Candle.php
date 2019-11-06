<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CandleRepository")
 */
class Candle implements \JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CurrencyPair", inversedBy="candles")
     */
    private $currencyPair;

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
     * @param mixed $volume
     */
    public function addToVolume($volume): void
    {
        $this->volume += $volume;
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
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            $this->getOpenTime() * 1000,
            $this->getOpenPrice(),
            $this->getHighPrice(),
            $this->getLowPrice(),
            $this->getClosePrice()
        ];
    }
}
