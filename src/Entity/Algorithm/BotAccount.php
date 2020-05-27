<?php

namespace App\Entity\Algorithm;

use App\Entity\Trade\TradeTypes;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="App\Repository\Algorithm\BotAccountRepository")
 */
class BotAccount
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm")
     * @var BotAlgorithm
     */
    private $algo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Data\Exchange")
     */
    private $exchange;

    /**
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @ORM\Column(type="smallint")
     */
    private $tradeStatus = TradeTypes::TRADE_SELL;

    /**
     * @ORM\Column(type="smallint")
     */
    private $mode = AlgoModes::NOT_ACTIVE;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
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

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return BotAlgorithm
     */
    public function getAlgo(): BotAlgorithm
    {
        return $this->algo;
    }

    /**
     * @param BotAlgorithm $algo
     */
    public function setAlgo(BotAlgorithm $algo): void
    {
        $this->algo = $algo;
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return bool
     */
    public function isLong()
    {
        return $this->tradeStatus == TradeTypes::TRADE_BUY;
    }

    /**
     * @return bool
     */
    public function isShort()
    {
        return $this->tradeStatus == TradeTypes::TRADE_SELL;
    }

    public function setLong()
    {
        $this->tradeStatus = TradeTypes::TRADE_BUY;
    }

    public function setShort()
    {
        $this->tradeStatus = TradeTypes::TRADE_SELL;
    }

    /**
     * @return int
     */
    public function getTradeStatus(): int
    {
        return $this->tradeStatus;
    }

}