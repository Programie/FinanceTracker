<?php
namespace com\selfcoders\financetracker;

use com\selfcoders\financetracker\models\State;
use com\selfcoders\financetracker\models\WatchListEntry;
use Doctrine\ORM\EntityManager;

class Monitoring
{
    private const CHECK_MK_STATE_OK = 0;
    private const CHECK_MK_STATE_WARNING = 1;
    private const CHECK_MK_STATE_CRITICAL = 2;
    private const CHECK_MK_STATE_UNKNOWN = 3;

    private EntityManager $entityManager;
    private int $nowTimestamp;
    private array $checkOutput = [];
    private int $checkState = self::CHECK_MK_STATE_OK;

    public function __construct()
    {
        $this->entityManager = Database::getEntityManager();
    }

    public function checkEntry(WatchListEntry $watchListEntry)
    {
        $stateMessage = "OK";

        /**
         * @var $state State|null
         */
        $state = $watchListEntry->getState();

        if ($state === null) {
            $this->checkState = self::CHECK_MK_STATE_CRITICAL;
            $stateMessage = "No state available (!!)";
        } else {
            $fetchedDate = $state->getFetched();

            if ($fetchedDate === null) {
                $this->checkState = self::CHECK_MK_STATE_CRITICAL;
                $stateMessage = "No fetch date available (!!)";
            } else {
                $fetchedDifference = $this->nowTimestamp - $fetchedDate->getTimestamp();

                if ($fetchedDifference >= 300) {
                    $this->checkState = self::CHECK_MK_STATE_CRITICAL;
                    $stateMessage = "Last fetched over 5 minutes ago (!!)";
                } elseif ($fetchedDifference >= 120) {
                    $this->checkState = self::CHECK_MK_STATE_WARNING;
                    $stateMessage = "Last fetched over 2 minutes ago (!)";
                }
            }
        }

        $this->checkOutput[] = sprintf("%s: %s", $watchListEntry->getName(), $stateMessage);
    }

    public function checkAllEntries()
    {
        $this->nowTimestamp = (new Date)->getTimestamp();
        $watchListEntries = $this->entityManager->getRepository(WatchListEntry::class)->findAll();

        foreach ($watchListEntries as $watchListEntry) {
            $this->checkEntry($watchListEntry);
        }

        printf("%d FinanceTracker_States - %s\n", $this->checkState, str_replace("\n", "\\n", implode("\\n", $this->checkOutput)));
    }
}