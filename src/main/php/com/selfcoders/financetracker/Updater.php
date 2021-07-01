<?php
namespace com\selfcoders\financetracker;

use com\selfcoders\financetracker\fetcher\Fetcher;
use com\selfcoders\financetracker\fetcher\ResponseData;
use com\selfcoders\financetracker\models\State;
use com\selfcoders\financetracker\models\WatchList;
use com\selfcoders\financetracker\models\WatchListEntry;
use com\selfcoders\financetracker\orm\WatchListEntryRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use Throwable;

class Updater
{
    private EntityManager $entityManager;
    private WatchListEntryRepository $watchListEntryRepository;
    /**
     * @var WatchList[]
     */
    private array $watchLists;

    public function __construct()
    {
        $this->entityManager = Database::getEntityManager();
        $this->watchListEntryRepository = $this->entityManager->getRepository(WatchListEntry::class);
        $this->watchLists = $this->entityManager->getRepository(WatchList::class)->findAll();
    }

    public function run()
    {
        while (true) {
            try {
                $this->doUpdate();
            } catch (Throwable $exception) {
                printf("Error on line %d in %s: %s\n%s", $exception->getLine(), $exception->getFile(), $exception->getMessage(), $exception->getTraceAsString());
            }

            sleep(5);
        }
    }

    private function getAllIsins()
    {
        $isinList = [];

        foreach ($this->watchLists as $watchList) {
            /**
             * @var $entry WatchListEntry
             */
            foreach ($watchList->getEntries() as $entry) {
                $isinList[] = $entry->getIsin();
            }
        }

        return array_filter(array_unique($isinList));
    }

    private function getIsinsToUpdate()
    {
        $now = (new Date)->getTimestamp();

        $isinList = [];

        foreach ($this->watchLists as $watchList) {
            /**
             * @var $entry WatchListEntry
             */
            foreach ($watchList->getEntries() as $entry) {
                $lastUpdate = $entry->getState()?->getLastUpdate();
                $updateInterval = $entry->getUpdateInterval() ?? $watchList->getUpdateInterval();

                if ($lastUpdate === null or $now - $lastUpdate->getTimestamp() >= $updateInterval) {
                    $isinList[] = $entry->getIsin();
                }
            }
        }

        return array_filter(array_unique($isinList));
    }

    private function doUpdate()
    {
        $isinList = $this->getIsinsToUpdate();

        // No need to update
        if (empty($isinList)) {
            return;
        }

        $fetcher = new Fetcher;

        foreach ($isinList as $isin) {
            $fetcher->add($isin);
        }

        $responseDataList = $fetcher->execute();

        $allStates = [];
        $allIsins = $this->getAllIsins();

        foreach ($this->entityManager->getRepository(State::class)->findAll() as $state) {
            /**
             * @var $isin string
             */
            $isin = $state->getIsin();

            $key = sprintf("%s:%s", $isin, $state->getPriceType());

            if (!in_array($isin, $allIsins)) {
                $this->entityManager->remove($state);
                continue;
            }

            $allStates[$key] = $state;
        }

        $this->entityManager->flush();

        /**
         * @var $newNotifications WatchListEntry
         */
        $newNotifications = [];

        try {
            foreach ($responseDataList as $responseData) {
                if ($responseData->name === null) {
                    fwrite(STDERR, sprintf("Missing name for ISIN %s\n", $responseData->isin));
                    continue;
                }
                if ($responseData->bidDate === null or $responseData->askDate === null) {
                    fwrite(STDERR, sprintf("Missing date for ISIN %s\n", $responseData->isin));
                    continue;
                }
                if ($responseData->bidPrice === null or $responseData->askPrice === null) {
                    fwrite(STDERR, sprintf("Missing price for ISIN %s\n", $responseData->isin));
                    continue;
                }

                $bidState = $this->buildState($allStates, $responseData, PriceType::BID);
                $askState = $this->buildState($allStates, $responseData, PriceType::ASK);

                $this->entityManager->persist($bidState);
                $this->entityManager->persist($askState);
                $this->entityManager->flush();

                $watchListEntries = $this->watchListEntryRepository->findByIsin($responseData->isin);
                foreach ($watchListEntries as $entry) {
                    switch ($entry->getWatchList()->getPriceType()) {
                        case PriceType::BID:
                            $entry->setState($bidState);
                            break;
                        case PriceType::ASK:
                            $entry->setState($askState);
                            break;
                    }

                    $watchList = $entry->getWatchList();
                    if ($watchList->isNotificationsEnabled()) {
                        list($limitType, $difference) = $entry->getReachedLimit();

                        if ($limitType !== null and $limitType !== $entry->getNotificationType() and $difference !== null) {
                            $entry->setNotified($limitType);

                            $newNotifications[] = $entry;
                        }
                    }

                    $this->entityManager->persist($entry);
                }
                $this->entityManager->flush();
            }
        } finally {
            foreach ($newNotifications as $entry) {
                $recipients = $entry->getWatchList()->getNotificationRecipients();

                foreach ($recipients as $recipient) {
                    try {
                        $recipient->sendForWatchListEntry($entry);
                    } catch (Exception $exception) {
                        echo $exception;
                    }
                }
            }
        }
    }

    private function buildState(array $list, ResponseData $responseData, string $priceType)
    {
        switch ($priceType) {
            case PriceType::ASK:
                $date = $responseData->askDate;
                $price = $responseData->askPrice;
                break;
            case PriceType::BID:
                $date = $responseData->bidDate;
                $price = $responseData->bidPrice;
                break;
            default:
                return null;
        }

        /**
         * @var $state State|null
         */
        $state = $list[sprintf("%s:%s", $responseData->isin, $priceType)] ?? null;

        if ($state === null) {
            $state = new State;
            $state->setIsin($responseData->isin);
            $state->setPriceType($priceType);
            $previousUpdate = null;
            $previousPrice = null;
        } else {
            $previousUpdate = $state->getUpdated();
            $previousPrice = $state->getPrice();
        }

        /**
         * @var $previousUpdate Date
         */
        if ($previousUpdate !== null and $previousUpdate->format("Y-m-d") !== $date->format("Y-m-d")) {
            $state->setDayStartPrice($price);
        }

        if ($previousPrice !== $price) {
            $state->setPreviousPrice($previousPrice);
        }

        $state->setName($responseData->name);
        $state->setUpdated($date);
        $state->setLastUpdate(new Date);
        $state->setPrice($price);

        return $state;
    }
}