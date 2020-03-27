<?php

namespace App\Entity\Trade;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TradeRepository")
 */
class Trade
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $orderId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm")
     */
    private $algo;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="float")
     */
    private $fillPrice;

    /**
     * @ORM\Column(type="integer")
     */
    private $timeStamp;

    /**
     * @ORM\Column(type="smallint")
     */
    private $type;

    /**
     * @ORM\Column(type="smallint")
     */
    private $status = TradeTypes::TRADE_SELL;

    /**
     * @ORM\Column(type="smallint")
     */
    private $mode;

    /**
     * @ORM\Column(type="float")
     */
    private $fees = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return mixed
     */
    public function getAlgo()
    {
        return $this->algo;
    }

    /**
     * @param mixed $algo
     */
    public function setAlgo($algo): void
    {
        $this->algo = $algo;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): void
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }

    /**
     * @param mixed $timeStamp
     */
    public function setTimeStamp($timeStamp): void
    {
        $this->timeStamp = $timeStamp;
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
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param mixed $mode
     */
    public function setMode($mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return mixed
     */
    public function getFillPrice()
    {
        return $this->fillPrice;
    }

    /**
     * @param mixed $fillPrice
     */
    public function setFillPrice($fillPrice): void
    {
        $this->fillPrice = $fillPrice;
    }

    /**
     * @return mixed
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * @param mixed $fees
     */
    public function setFees($fees): void
    {
        $this->fees = $fees;
    }
}
