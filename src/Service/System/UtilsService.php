<?php


namespace App\Service\System;


use App\Service\ThirdPartyAPIs\TelegramBot;

class UtilsService
{
    const TEMPERATURE_THRESHOLD = 50;

    /**
     * @var TelegramBot
     */
    private $telegramBot;

    /**
     * UtilsService constructor.
     * @param TelegramBot $telegramBot
     */
    public function __construct(TelegramBot $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }


    public function checkTemperature()
    {
        if(strpos(php_uname(), 'Linux') !== false) {
            $output = shell_exec("cat /sys/class/thermal/thermal_zone0/temp");
            if($output) {
                $temperature = (int)$output / 1000;
                if($temperature > self::TEMPERATURE_THRESHOLD) {
                    $this->telegramBot->send($_ENV['TELEGRAM_USER_ID'], "WARNING: CPU temperature at $temperature °C");
                }
            }
        }
    }
}