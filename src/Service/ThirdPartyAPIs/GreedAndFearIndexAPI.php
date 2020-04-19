<?php


namespace App\Service\ThirdPartyAPIs;


use App\Entity\Data\Currency;


class GreedAndFearIndexAPI extends ThirdPartyAPI
{

    /**
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getData()
    {
        try {
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute(),
                [
                    'query' => [
                        'limit' => 1000
                    ]
                ]);
            $rawData = $response->toArray();
            $data = $rawData['data'];
        } catch (\Exception $e) {
            throw $e;
        }
        return $data;
    }


    /**
     * @return string
     */
    private function getAPIBaseRoute() : string
    {
        return "https://api.alternative.me/fng/";
    }
}