<?php


namespace App\Service;


class TwitterAPI extends ThirdPartyAPI
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
        $token = $_ENV['TWITTER_ACCESS_TOKEN'];

        try {
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute(),
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