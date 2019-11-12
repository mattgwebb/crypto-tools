<?php

namespace App\Entity;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\CurrencyPair")
     */
    private $currencyPair;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

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

    public function getId(): ?int
    {
        return $this->id;
    }
}
