<?php


namespace App\Service;


use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class ThirdPartyAPI
{
    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * ApiInterface constructor.
     */
    public function __construct()
    {
        $this->httpClient = HttpClient::create();
    }
}