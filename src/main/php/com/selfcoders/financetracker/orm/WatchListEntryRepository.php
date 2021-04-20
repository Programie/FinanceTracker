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
     * @param string $isin
     * @return WatchListEntry|null
     */
    public function findByListAndIsin(string $watchList, string $isin): ?WatchListEntry
    {
        return $this->createQueryBuilder("entry")
            ->leftJoin("entry.watchList", "watchList")
            ->where("watchList.name = :name")
            ->andWhere("entry.isin = :isin")
            ->setParameter("name", $watchList)
            ->setParameter("isin", $isin)
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