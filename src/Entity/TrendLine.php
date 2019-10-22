<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TrendLineRepository")
 */
class TrendLine
{
    const TYPE_SUPPORT = 1;
    const TYPE_RESISTANCE = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="float")
     */
    private $startPrice;

    /**
     * @ORM\Column(type="float")
     */
    private $endPrice;

    /**
     * @ORM\Column(type="integer")
     */
    private $startTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $endTime;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency", inversedBy="trendLines")
     */
    private $currency;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getStartPrice()
    {
        return $this->startPrice;
    }

    /**
     * @param mixed $startPrice
     */
    public function setStartPrice($startPrice): void
    {
        $this->startPrice = $startPrice;
    }

    /**
     * @return mixed
     */
    public function getEndPrice()
    {
        return $this->endPrice;
    }

    /**
     * @param mixed $endPrice
     */
    public function setEndPrice($endPrice): void
    {
        $this->endPrice = $endPrice;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param mixed $startTime
     */
    public function setStartTime($startTime): void
    {
        $this->startTime = $startTime;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param mixed $endTime
     */
    public function setEndTime($endTime): void
    {
        $this->endTime = $endTime;
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
