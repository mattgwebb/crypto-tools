<?php


namespace App\Entity\Algorithm;


class StrategyResult implements \JsonSerializable
{
    const TRADE_SHORT = -1;
    const TRADE_LONG = 1;
    const NO_TRADE = 0;

    private $tradeResult = self::NO_TRADE;

    private $fromInvalidation = false;

    private $extraData = [];

    /**
     * @return mixed
     */
    public function getTradeResult()
    {
        return $this->tradeResult;
    }

    /**
     * @return bool
     */
    public function isLong()
    {
        return $this->tradeResult == self::TRADE_LONG;
    }

    /**
     * @return bool
     */
    public function isShort()
    {
        return $this->tradeResult == self::TRADE_SHORT;
    }

    /**
     * @return bool
     */
    public function noTrade()
    {
        return $this->tradeResult == self::NO_TRADE;
    }

    /**
     * @param mixed $tradeResult
     */
    public function setTradeResult($tradeResult): void
    {
        $this->tradeResult = $tradeResult;
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
     * @return bool
     */
    public function isFromInvalidation(): bool
    {
        return $this->fromInvalidation;
    }

    /**
     * @param bool $fromInvalidation
     */
    public function setFromInvalidation(bool $fromInvalidation): void
    {
        $this->fromInvalidation = $fromInvalidation;
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
            "extraData" => $this->getExtraData()
        ];
    }
}