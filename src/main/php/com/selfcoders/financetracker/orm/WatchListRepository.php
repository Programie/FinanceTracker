<?php
namespace com\selfcoders\financetracker\orm;

use com\selfcoders\financetracker\models\WatchList;
use Doctrine\ORM\EntityRepository;

class WatchListRepository extends EntityRepository
{
    public function findByName(string $name): ?WatchList
    {
        return $this->findOneBy(["name" => $name]);
    }

    /**
     * @return WatchList[]
     */
    public function findEnabled(?array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->findBy(["enabled" => true], $orderBy, $limit, $offset);
    }
}