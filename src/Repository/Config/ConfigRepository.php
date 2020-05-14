<?php

namespace App\Repository\Config;

use App\Entity\Config\ConfigItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ConfigItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigItem[]    findAll()
 * @method ConfigItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigItem::class);
    }

    /**
     * @param string $section
     * @param string $name
     * @return ConfigItem|null
     */
    public function getConfig(string $section, string $name)
    {
        return $this->findOneBy(["section" => $section, "name" => $name]);
    }
}
