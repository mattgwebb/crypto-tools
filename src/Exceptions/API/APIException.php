<?php


namespace App\Exceptions\API;


class APIException extends \Exception
{

    /**
     * @var int
     */
    private $errorCode;

    /**
     * APIException constructor.
     * @param int $code
     * @param string $message
     */
    public function __construct(int $code, string $message)
    {
        $this->errorCode = $code;
        parent::__construct($message);
    }

    public function __toString()
    {
        return "API ERROR {$this->errorCode}: ".$this->getMessage();
    }
}