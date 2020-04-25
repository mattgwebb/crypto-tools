<?php

namespace App\Entity\Algorithm;

use App\Entity\TechnicalAnalysis\Strategy;
use App\Entity\Trade\TradeTypes;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Algorithm\AlgorithmStrategyRepository")
 * @ORM\Table(name="algo_strategies")
 */
class AlgorithmStrategy
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Algorithm\BotAlgorithm", inversedBy="strategies")
     * @var BotAlgorithm
     */
    private $algo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TechnicalAnalysis\Strategy")
     * @var Strategy
     */
    private $strategy;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $type;

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
     * @return Strategy
     */
    public function getStrategy(): Strategy
    {
        return $this->strategy;
    }

    /**
     * @param Strategy $strategy
     */
    public function setStrategy(Strategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isForEntry()
    {
        return $this->getType() == TradeTypes::TRADE_BUY;
    }

    /**
     * @return bool
     */
    public function isForExit()
    {
        return $this->getType() == TradeTypes::TRADE_SELL;
    }
}
