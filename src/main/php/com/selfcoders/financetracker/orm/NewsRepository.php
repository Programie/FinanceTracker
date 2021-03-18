<?php
namespace com\selfcoders\financetracker\orm;

use com\selfcoders\financetracker\models\News;
use Doctrine\ORM\EntityRepository;

class NewsRepository extends EntityRepository
{
    /**
     * @param string $isin
     * @return News|null
     */
    public function findByIsin(string $isin): ?News
    {
        return $this->findOneBy(["isin" => $isin]);
    }
}