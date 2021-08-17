<?php
namespace com\selfcoders\financetracker;

use com\selfcoders\financetracker\fetcher\BaseFetcher;
use com\selfcoders\financetracker\fetcher\Fetcher;
use com\selfcoders\financetracker\fetcher\FetcherHelper;
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

    public function __construct()
    {
        $this->entityManager = Database::getEntityManager();
    }

    public function checkEntry(WatchListEntry $watchListEntry)
    {
        $checkState = self::CHECK_MK_STATE_OK;
        $stateMessage = "OK";

        /**
         * @var $fetcherClass Fetcher
         */
        $fetcherClass = BaseFetcher::getFetcherClass($watchListEntry->getIsin(), BaseFetcher::DATASOURCE_LS);

        if ($fetcherClass === null or $fetcherClass::shouldUpdate(0)) {
            /**
             * @var $state State|null
             */
            $state = $watchListEntry->getState();

            if ($state === null) {
                $checkState = self::CHECK_MK_STATE_CRITICAL;
                $stateMessage = "No state available (!!)";
            } else {
                $fetchedDate = $state->getFetched();

                if ($fetchedDate === null) {
                    $checkState = self::CHECK_MK_STATE_CRITICAL;
                    $stateMessage = "No fetch date available (!!)";
                } else {
                    $fetchedDifference = $this->nowTimestamp - $fetchedDate->getTimestamp();

                    if ($fetchedDifference >= 300) {
                        $checkState = self::CHECK_MK_STATE_CRITICAL;
                        $stateMessage = "Last fetched over 5 minutes ago (!!)";
                    } elseif ($fetchedDifference >= 120) {
                        $checkState = self::CHECK_MK_STATE_WARNING;
                        $stateMessage = "Last fetched over 2 minutes ago (!)";
                    }
                }
            }
        }

        $checkOutput = sprintf("%s %s: %s", $watchListEntry->getIsin(), $watchListEntry->getName(), $stateMessage);

        return [$checkState, $checkOutput];
    }

    public function checkAllEntries()
    {
        $overallState = self::CHECK_MK_STATE_OK;
        $this->nowTimestamp = (new DateTime)->getTimestamp();
        $watchListEntries = $this->entityManager->getRepository(WatchListEntry::class)->findAll();
        $messageLines = [sprintf("Checked %d entries", count($watchListEntries))];

        foreach ($watchListEntries as $watchListEntry) {
            list($checkState, $checkOutput) = $this->checkEntry($watchListEntry);

            $overallState = max($overallState, $checkState);

            if ($checkState !== self::CHECK_MK_STATE_OK) {
                $messageLines[] = $checkOutput;
            }
        }

        printf("%d FinanceTracker_States - %s\n", $overallState, str_replace("\n", "\\n", implode("\\n", $messageLines)));
    }
}