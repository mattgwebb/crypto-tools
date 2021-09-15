<?php

namespace App\Entity\Trade;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Trade\BotAccountHistoricalPortfolioRepository")
 */
class BotAccountHistoricalPortfolio
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Algorithm\BotAccount")
     */
    private $botAccount;

    /**
     * @ORM\Column(type="integer")
     */
    private $timeStamp;

    /**
     * @ORM\Column(type="float")
     */
    private $totalValue;

    /**
     * @ORM\Column(type="float")
     */
    private $pnlPercentage = 0.00;

    /**
     * @ORM\Column(type="float")
     */
    private $pnlAmount = 0.00;

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
    public function getTotalValue()
    {
        return $this->totalValue;
    }

    /**
     * @param mixed $totalValue
     */
    public function setTotalValue($totalValue): void
    {
        $this->totalValue = round($totalValue, 2);
    }

    /**
     * @return float
     */
    public function getPnlPercentage(): float
    {
        return $this->pnlPercentage;
    }

    /**
     * @param float $pnlPercentage
     */
    public function setPnlPercentage(float $pnlPercentage): void
    {
        $this->pnlPercentage = round($pnlPercentage, 2);
    }

    /**
     * @return float
     */
    public function getPnlAmount(): float
    {
        return $this->pnlAmount;
    }

    /**
     * @param float $pnlAmount
     */
    public function setPnlAmount(float $pnlAmount): void
    {
        $this->pnlAmount = round($pnlAmount, 2);
    }
}
