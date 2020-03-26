<?php


namespace App\Service\ThirdPartyAPIs;


use App\Entity\Data\Currency;


class WhaleAlertAPI extends ThirdPartyAPI
{

    /**
     * @param Currency $currency
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getTransactions(Currency $currency)
    {

        try {
            $response = $this->httpClient->request('GET', $this->getAPIBaseRoute().'transactions',
                [
                    'query' => [
                        'start' => time() - 3590,
                        'currency' => $currency->getSymbol()
                    ],
                    'headers' => $this->getAuthHeader(),
                ]);
            $data = $response->toArray();
            $transactions = $data['transactions'];
        } catch (\Exception $e) {
            throw $e;
        }
        return $transactions;
    }

    /**
     * @return array
     */
    private function getAuthHeader() : array
    {
        return [
            'X-WA-API-KEY' => $_ENV['WHALE_ALERT_KEY']
        ];
    }

    /**
     * @return string
     */
    private function getAPIBaseRoute() : string
    {
        return "https://api.whale-alert.io/v1/";
    }
}