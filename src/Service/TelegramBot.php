<?php


namespace App\Service;

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
}
