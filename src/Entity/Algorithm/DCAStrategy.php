<?php

namespace App\Entity\Algorithm;

use App\Entity\Data\TimeFrames;
use App\Entity\Trade\DCAFrequencies;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeTypes;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="App\Repository\Algorithm\DCAStrategyRepository")
 */
class DCAStrategy
{
    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\Algorithm\BotAccount", inversedBy="dcaStrategy")
     */
    private $botAccount;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Data\CurrencyPair")
     */
    private $currencyPair;

    /**
     * @ORM\Column(type="float")
     */
    private $tradeAmount;

    /**
     * @ORM\Column(type="float")
     */
    private $dipPercentage;

    /**
     * @ORM\Column(type="string")
     */
    private $frequency;

    /**
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Trade\Trade")
     */
    private $lastTrade;

    /**
     * @ORM\Column(type="smallint")
     */
    private $mode = AlgoModes::NOT_ACTIVE;

    /**
     * @return mixed
     */
    public function getBotAccount()
    {
        return $this->botAccount;
    }

    /**
     * @param mixed $botAccount
     */
    public function setBotAccount($botAccount): void
    {
        $this->botAccount = $botAccount;
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
     * @return mixed
     */
    public function getTradeAmount()
    {
        return $this->tradeAmount;
    }

    /**
     * @param mixed $tradeAmount
     */
    public function setTradeAmount($tradeAmount): void
    {
        $this->tradeAmount = $tradeAmount;
    }

    /**
     * @return mixed
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * @param mixed $frequency
     */
    public function setFrequency($frequency): void
    {
        $this->frequency = $frequency;
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
     * @return Trade|null
     */
    public function getLastTrade()
    {
        return $this->lastTrade;
    }

    /**
     * @param mixed $lastTrade
     */
    public function setLastTrade($lastTrade): void
    {
        $this->lastTrade = $lastTrade;
    }

    /**
     * @return mixed
     */
    public function getDipPercentage()
    {
        return $this->dipPercentage;
    }

    /**
     * @param mixed $dipPercentage
     */
    public function setDipPercentage($dipPercentage): void
    {
        $this->dipPercentage = $dipPercentage;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function readyForNewTrade()
    {
        $lastTrade = $this->getLastTrade();
        if(!$lastTrade) {
            return true;
        }

        $tradeDate = new \DateTime('@'.$lastTrade->getTimeStamp());
        $now = new \DateTime();

        if($tradeDate->format('Y') != $now->format('Y')) {
            return true;
        } else if ($tradeDate->format('m') != $now->format('m')) {
            return true;
        } else if(in_array($this->getFrequency(), [DCAFrequencies::WEEKLY, DCAFrequencies::DAILY]) && ($tradeDate->format('W') != $now->format('W'))) {
            return true;
        } else if($this->getFrequency() == DCAFrequencies::DAILY && ($tradeDate->format('d') != $now->format('d'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return int
     */
    public function getLastTimestampToBuy()
    {
        $periodEnd = new \DateTime();
        if($this->getFrequency() == DCAFrequencies::MONTHLY) {
            $periodEnd->modify('last day of this month');
        } else if($this->getFrequency() == DCAFrequencies::WEEKLY) {
            $periodEnd = $periodEnd->modify('Sunday this week');
        }
        $periodEnd->setTime(23, 29, 59);
        return $periodEnd->getTimestamp();
    }
}