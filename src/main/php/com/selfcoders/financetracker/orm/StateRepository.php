<?php
namespace com\selfcoders\financetracker\orm;

use com\selfcoders\financetracker\models\State;
use Doctrine\ORM\EntityRepository;

class StateRepository extends EntityRepository
{
    public function findByIsin(string $isin): ?State
    {
        return $this->findOneBy(["isin" => $isin]);
    }
}