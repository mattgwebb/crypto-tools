<?php


namespace App\Service\Config;


use App\Entity\Config\ConfigItem;
use App\Repository\Config\ConfigRepository;

class ConfigService
{
    /**
     * @var ConfigRepository
     */
    private $repo;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * ConfigService constructor.
     * @param ConfigRepository $repo
     */
    public function __construct(ConfigRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @param string $section
     * @param string $name
     * @return ConfigItem
     */
    public function getConfig(string $section, string $name)
    {
        if(!isset($this->cache[$section][$name])) {
            $this->cache[$section][$name] = $this->repo->getConfig($section, $name);
        }
        return $this->cache[$section][$name];
    }
}

