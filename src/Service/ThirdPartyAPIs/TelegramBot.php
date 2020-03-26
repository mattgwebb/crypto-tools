<?php


namespace App\Service\ThirdPartyAPIs;

use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Trade\Trade;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\API\APIException;
use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use TgBotApi\BotApiBase\ApiClient;
use TgBotApi\BotApiBase\BotApi;
use TgBotApi\BotApiBase\BotApiNormalizer;
use TgBotApi\BotApiBase\Method\SendMessageMethod;

class TelegramBot
{

    /** @var BotApi */
    private $bot;

    /**
     * TelegramBot constructor.
     */
    public function __construct()
    {
        $key = $_ENV['TELGRAM_BOT_KEY'];
        $requestFactory = new RequestFactory();
        $streamFactory = new StreamFactory();
        $client = new Client();

        $apiClient = new ApiClient($requestFactory, $streamFactory, $client);
        $this->bot = new BotApi($key, $apiClient, new BotApiNormalizer());
    }

    /**
     * @param $userID
     * @param string $message
     */
    public function send($userID, string $message)
    {
        try {
            $this->bot->send(SendMessageMethod::create($userID, $message));
        } catch (\Exception $exception) {
            $test = 0;
        }
    }

    /**
     * @param $userID
     * @param BotAlgorithm $algo
     * @param Trade $trade
     */
    public function sendNewTradeMessage($userID, BotAlgorithm $algo, Trade $trade)
    {
        $symbol = $algo->getCurrencyPair()->getSymbol();

        $tradeType = $trade->getType() == TradeTypes::TRADE_BUY ? "BUY" : "SELL";
        $message = "NEW SIGNAL \n";
        $message .= "Symbol: $symbol \n";
        $message .= "Signal type: $tradeType \n";
        $message .= "Price: {$trade->getPrice()} \n";
        $message .= "Algo: {$algo->getName()} \n";

        $this->send($userID, $message);
    }

    /**
     * @param $userID
     * @param BotAlgorithm $algo
     * @param APIException $exception
     */
    public function sendNewErrorMessage($userID, BotAlgorithm $algo, APIException $exception)
    {
        $symbol = $algo->getCurrencyPair()->getSymbol();

        $message = "TRADE ERROR \n";
        $message .= "Symbol: $symbol \n";
        $message .= "Algo: {$algo->getName()} \n";
        $message .= "$exception \n";

        $this->send($userID, $message);
    }
}
