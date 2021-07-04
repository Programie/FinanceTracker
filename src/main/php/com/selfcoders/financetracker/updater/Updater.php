<?php
namespace com\selfcoders\financetracker\updater;

use com\selfcoders\financetracker\Database;
use com\selfcoders\financetracker\Date;
use com\selfcoders\financetracker\fetcher\FetcherHelper;
use com\selfcoders\financetracker\fetcher\ResponseData;
use com\selfcoders\financetracker\models\State;
use com\selfcoders\financetracker\models\WatchList;
use com\selfcoders\financetracker\models\WatchListEntry;
use com\selfcoders\financetracker\orm\WatchListEntryRepository;
use com\selfcoders\financetracker\PriceType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;

class Updater
{
    private const UPDATE_INTERVAL_FAST = 10;
    private const UPDATE_INTERVAL_NORMAL = 60;

    private EntityManager $entityManager;
    private WatchListEntryRepository $watchListEntryRepository;
    private int $lastFastUpdate = 0;
    private int $lastNormalUpdate = 0;

    public function __construct()
    {
        $this->entityManager = Database::getEntityManager();
        $this->watchListEntryRepository = $this->entityManager->getRepository(WatchListEntry::class);
    }

    public function run()
    {
        while (true) {
            $this->entityManager->clear();
            $this->doUpdate();

            sleep(min(self::UPDATE_INTERVAL_FAST, self::UPDATE_INTERVAL_NORMAL));
        }
    }

    private function log(string $string, $stream = STDOUT)
    {
        fwrite($stream, sprintf("[%s] %s\n", date("r"), $string));
    }

    private function getWatchlists()
    {
        return $this->entityManager->getRepository(WatchList::class)->findAll();
    }

    private function getAllIsins()
    {
        $isinList = [];

        foreach ($this->getWatchlists() as $watchList) {
            /**
             * @var $entry WatchListEntry
             */
            foreach ($watchList->getEntries() as $entry) {
                $isinList[] = $entry->getIsin();
            }
        }

        return array_filter(array_unique($isinList));
    }

    private function getGroupedUpdateEntries()
    {
        $perIsinUpdateEntries = [];

        foreach ($this->getWatchlists() as $watchList) {
            /**
             * @var $entry WatchListEntry
             */
            foreach ($watchList->getEntries() as $entry) {
                $isin = $entry->getIsin();
                $wkn = $entry->getWkn();
                $hasState = $entry->getState() !== null;

                if ($entry->isFastUpdateIntervalEnabled()) {
                    $perIsinUpdateEntries[$isin] = new UpdateEntry(UpdateEntry::TYPE_FAST, $isin, $wkn, $hasState);
                } elseif (!isset($perIsinUpdateEntries[$isin])) {
                    $perIsinUpdateEntries[$isin] = new UpdateEntry(UpdateEntry::TYPE_NORMAL, $isin, $wkn, $hasState);
                }
            }
        }

        $fastUpdateEntries = [];
        $normalUpdateEntries = [];

        foreach ($perIsinUpdateEntries as $updateEntry) {
            if ($updateEntry->updateType === UpdateEntry::TYPE_FAST) {
                $fastUpdateEntries[] = $updateEntry;
            } else {
                $normalUpdateEntries[] = $updateEntry;
            }
        }

        return [$fastUpdateEntries, $normalUpdateEntries];
    }

    /**
     * @param UpdateEntry[] $updateEntries
     * @param bool $force
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function doUpdateForEntries(array $updateEntries, bool $force)
    {
        $isinWknList = [];

        foreach ($updateEntries as $updateEntry) {
            if (!$updateEntry->hasState) {
                $force = true;
            }

            $isinWknList[] = [$updateEntry->isin, $updateEntry->wkn];
        }

        $responseDataList = FetcherHelper::getData($isinWknList, $force);

        $allStates = [];
        $allIsins = $this->getAllIsins();

        /**
         * @var State $state
         */
        foreach ($this->entityManager->getRepository(State::class)->findAll() as $state) {
            $isin = $state->getIsin();

            $key = sprintf("%s:%s", $isin, $state->getPriceType());

            if (!in_array($isin, $allIsins)) {
                $this->entityManager->remove($state);
                continue;
            }

            $allStates[$key] = $state;
        }

        /**
         * @var $newNotifications WatchListEntry
         */
        $newNotifications = [];

        try {
            foreach ($responseDataList as $responseData) {
                if ($responseData->name === null) {
                    $this->log(sprintf("[%s] Missing name", $responseData->isin), STDERR);
                    continue;
                }
                if ($responseData->bidDate === null or $responseData->askDate === null) {
                    $this->log(sprintf("[%s] Missing date", $responseData->isin), STDERR);
                    continue;
                }
                if ($responseData->bidPrice === null or $responseData->askPrice === null) {
                    $this->log(sprintf("[%s] Missing price", $responseData->isin), STDERR);
                    continue;
                }

                $this->log(sprintf("[%s] Updating price to %f (bid) / %f (ask)", $responseData->isin, $responseData->bidPrice, $responseData->askPrice));

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

    private function doUpdate()
    {
        list($fastUpdateEntries, $normalUpdateEntries) = $this->getGroupedUpdateEntries();

        if (time() - $this->lastFastUpdate >= self::UPDATE_INTERVAL_FAST) {
            $this->doUpdateForEntries($fastUpdateEntries, !$this->lastFastUpdate);
            $this->lastFastUpdate = time();
        }

        if (time() - $this->lastNormalUpdate >= self::UPDATE_INTERVAL_NORMAL) {
            $this->doUpdateForEntries($normalUpdateEntries, !$this->lastNormalUpdate);
            $this->lastNormalUpdate = time();
        }

        $this->entityManager->flush();
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
        $state->setFetched($responseData->fetchDate);
        $state->setUpdated($date);
        $state->setPrice($price);

        return $state;
    }
}