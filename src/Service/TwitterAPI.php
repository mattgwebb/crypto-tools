<?php


namespace App\Service;


use Symfony\Component\HttpClient\HttpClient;

class TwitterAPI
{

    /**
     * @param string $query
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function searchTweets(string $query)
    {
        $client = HttpClient::create();
        $token = $_ENV['TWITTER_ACCESS_TOKEN'];

        try {
            $response = $client->request('GET', $this->getAPIBaseRoute(),
                [
                    'query' => [
                        'query' => $query
                    ],
                    'headers' => [
                        'Authorization' => "Bearer $token",
                    ],
                ]);
            $data = $response->toArray();
        } catch (\Exception $e) {
            throw $e;
        }
        return $data;
    }

    private function getAPIBaseRoute() : string
    {
        return "https://api.twitter.com/1.1/tweets/search/30day/dev.json";
    }
}