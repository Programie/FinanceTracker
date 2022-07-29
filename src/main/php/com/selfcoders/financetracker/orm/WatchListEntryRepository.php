<?php
namespace com\selfcoders\financetracker\orm;

use com\selfcoders\financetracker\models\WatchListEntry;
use Doctrine\ORM\EntityRepository;

class WatchListEntryRepository extends EntityRepository
{
    /**
     * @param string $watchList
     * @return WatchListEntry[]
     */
    public function findByList(string $watchList): array
    {
        return $this->createQueryBuilder("entry")
            ->leftJoin("entry.watchList", "watchList")
            ->where("watchList.name = :name")
            ->setParameter("name", $watchList)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $watchList
     * @param int $id
     * @return WatchListEntry|null
     */
    public function findByListAndId(string $watchList, int $id): ?WatchListEntry
    {
        return $this->createQueryBuilder("entry")
            ->leftJoin("entry.watchList", "watchList")
            ->where("watchList.name = :name")
            ->andWhere("entry.id = :id")
            ->setParameter("name", $watchList)
            ->setParameter("id", $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $isin
     * @return WatchListEntry[]
     */
    public function findByIsin(string $isin)
    {
        return $this->findBy(["isin" => $isin]);
    }
}