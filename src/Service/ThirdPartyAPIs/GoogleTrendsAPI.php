<?php


namespace App\Service\ThirdPartyAPIs;


use Google\GTrends;

class GoogleTrendsAPI
{

    /**
     * @var GTrends
     */
    private $gTrends;

    /**
     * GoogleTrendsAPI constructor.
     */
    public function __construct()
    {
        $options = [
            'hl'  => 'en-US',
            'tz'  => -60, # last hour
            'geo' => 'IE',
        ];
        $this->gTrends = new GTrends($options);
    }

    /**
     * @param string $query
     * @return array|bool
     * @throws \Exception
     */
    public function interestOverTime(string $query)
    {
        return @$this->gTrends->interestOverTime($query);
    }
}