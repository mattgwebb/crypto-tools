<?php


namespace App\Service\ThirdPartyAPIs;

use App\Entity\Algorithm\BotAccount;
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
     * @param BotAccount $botAccount
     * @param BotAlgorithm $algo
     * @param Trade $trade
     */
    public function sendNewTradeMessage(BotAccount $botAccount, BotAlgorithm $algo, Trade $trade)
    {
        $userID = $_ENV["TELEGRAM_USER_ID_BOT_{$botAccount->getId()}"];
        $symbol = $algo->getCurrencyPair()->getSymbol();

        $price = round($trade->getFillPrice(), 2);
        $cost = round($trade->getFillPrice() * $trade->getAmount(), 2);

        $tradeType = $trade->getType() == TradeTypes::TRADE_BUY ? "BUY" : "SELL";
        $message = "NEW SIGNAL \n";
        $message .= "Symbol: $symbol \n";
        $message .= "Signal type: $tradeType \n";
        $message .= "Price: $price \n";
        $message .= "Amount: {$trade->getAmount()} \n";
        $message .= "Cost: $cost {$algo->getCurrencyPair()->getSecondCurrency()->getSymbol()}\n";
        $message .= "Algo: {$algo->getName()} \n";

        $this->send($userID, $message);
    }

    /**
     * @param BotAccount $botAccount
     * @param Trade $trade
     */
    public function sendNewDCATradeMessage(BotAccount $botAccount, Trade $trade)
    {
        $strategy = $botAccount->getDcaStrategy();

        $userID = $_ENV["TELEGRAM_USER_ID_BOT_{$botAccount->getId()}"];
        $symbol = $strategy->getCurrencyPair()->getSymbol();

        $price = round($trade->getFillPrice(), 2);
        $cost = round($trade->getFillPrice() * $trade->getAmount(), 2);

        $message = "NEW DCA BUY \n";
        $message .= "Symbol: $symbol \n";
        $message .= "Price: $price \n";
        $message .= "Amount: {$trade->getAmount()} \n";
        $message .= "Cost: $cost {$strategy->getCurrencyPair()->getSecondCurrency()->getSymbol()}\n";

        $this->send($userID, $message);
    }

    /**
     * @param $userID
     * @param string $description
     * @param APIException $exception
     */
    public function sendNewErrorMessage($userID, string $description, APIException $exception)
    {
        $message = "TRADE ERROR \n";
        $message .= "Description: $description \n";
        $message .= "$exception \n";

        $this->send($userID, $message);
    }
}
