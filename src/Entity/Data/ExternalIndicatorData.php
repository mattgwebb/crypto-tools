<?php

namespace App\Entity\Data;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Data\ExternalIndicatorDataRepository")
 */
class ExternalIndicatorData
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
    private $openTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $closeTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $indicatorValue;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Data\ExternalIndicatorDataType", inversedBy="data")
     */
    private $indicatorType;

    /**
     * ExternalIndicatorData constructor.
     * @param int $closeTime
     * @param float $indicatorValue
     * @param ExternalIndicatorDataType $indicatorType
     */
    public function __construct(int $closeTime, float $indicatorValue, ExternalIndicatorDataType $indicatorType)
    {
        $this->openTime = 0;
        $this->closeTime = $closeTime;
        $this->indicatorValue = $indicatorValue;
        $this->indicatorType = $indicatorType;
    }

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
    public function getIndicatorValue()
    {
        return $this->indicatorValue;
    }

    /**
     * @param mixed $indicatorValue
     */
    public function setIndicatorValue($indicatorValue): void
    {
        $this->indicatorValue = $indicatorValue;
    }

    /**
     * @return mixed
     */
    public function getIndicatorType()
    {
        return $this->indicatorType;
    }

    /**
     * @param mixed $indicatorType
     */
    public function setIndicatorType($indicatorType): void
    {
        $this->indicatorType = $indicatorType;
    }
}
