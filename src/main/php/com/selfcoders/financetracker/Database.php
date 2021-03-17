<?php
namespace com\selfcoders\financetracker;

use com\selfcoders\financetracker\orm\DateType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class Database
{
    private static ?EntityManager $entityManager = null;

    public static function init()
    {
        Type::overrideType("datetime", DateType::class);
        Type::overrideType("date", DateType::class);

        $connection = [
            "driver" => "pdo_mysql",
            "host" => getenv("DATABASE_HOST"),
            "dbname" => getenv("DATABASE_USERNAME"),
            "user" => getenv("DATABASE_USERNAME"),
            "password" => getenv("DATABASE_PASSWORD")
        ];

        $config = Setup::createAnnotationMetadataConfiguration([SRC_ROOT], isDevMode: true, useSimpleAnnotationReader: false);
        self::$entityManager = EntityManager::create($connection, $config);
    }

    public static function getEntityManager(): EntityManager
    {
        if (self::$entityManager === null) {
            self::init();
        }

        return self::$entityManager;
    }
}