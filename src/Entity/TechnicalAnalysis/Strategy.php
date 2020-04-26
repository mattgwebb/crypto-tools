<?php

namespace App\Entity\TechnicalAnalysis;

use App\Entity\Algorithm\StrategyTypes;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TechnicalAnalysis\StrategyRepository")
 */
class Strategy
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
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
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
     * @return bool
     */
    public function isDivergenceStrategy()
    {
        return in_array($this->getName(), StrategyTypes::DIVERGENCE_STRATEGIES);
    }

    /**
     * @return bool
     */
    public function isOscillatorStrategy()
    {
        return in_array($this->getName(), StrategyTypes::OSCILLATOR_STRATEGIES);
    }

    /**
     * @return bool
     */
    public function isCrossoverStrategy()
    {
        return in_array($this->getName(), StrategyTypes::CROSSOVER_STRATEGIES);
    }

    /**
     * @return bool
     */
    public function isMovingAverageStrategy()
    {
        return in_array($this->getName(), StrategyTypes::MOVING_AVERAGE_STRATEGIES);
    }
}
