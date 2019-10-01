<?php


namespace App\Entity;


class IndicatorResult implements \JsonSerializable
{
    const TRADE_SHORT = -1;
    const TRADE_LONG = 1;
    const NO_TRADE = 0;

    private $tradeResult = self::NO_TRADE;

    private $type;

    private $value = 0;

    private $extraData = [];

    /**
     * IndicatorResult constructor.
     * @param $tradeResult
     * @param $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }


    /**
     * @return mixed
     */
    public function getTradeResult()
    {
        return $this->tradeResult;
    }

    /**
     * @param mixed $tradeResult
     */
    public function setTradeResult($tradeResult): void
    {
        $this->tradeResult = $tradeResult;
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
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param array $extraData
     */
    public function setExtraData(array $extraData): void
    {
        $this->extraData = $extraData;
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
            "result" => $this->getTradeResult(),
            "type" => $this->getType(),
            "value" => $this->getValue(),
            "extraData" => $this->getExtraData()
        ];
    }
}