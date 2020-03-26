<?php


namespace App\Entity\Trade;


use App\Entity\Data\CurrencyPair;

class BookOrder
{
    /**
     * @var CurrencyPair
     */
   private $currencyPair;

   /**
    * @var float
    */
   private $price;

   /**
    * @var float
    */
   private $quantity;

   /**
    * @var int
    */
   private $type;

    /**
     * BookOrder constructor.
     * @param CurrencyPair $currencyPair
     * @param int $type
     * @param float $price
     * @param float $quantity
     */
    public function __construct(CurrencyPair $currencyPair, int $type, float $price, float $quantity)
    {
        $this->currencyPair = $currencyPair;
        $this->type = $type;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    /**
     * @return CurrencyPair
     */
    public function getCurrencyPair(): CurrencyPair
    {
        return $this->currencyPair;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }
}