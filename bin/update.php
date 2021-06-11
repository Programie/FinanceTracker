#! /usr/bin/env php
<?php
use com\selfcoders\financetracker\Database;
use com\selfcoders\financetracker\Date;
use com\selfcoders\financetracker\fetcher\Fetcher;
use com\selfcoders\financetracker\models\State;
use com\selfcoders\financetracker\models\WatchList;
use com\selfcoders\financetracker\models\WatchListEntry;
use com\selfcoders\financetracker\PriceType;

require_once __DIR__ . "/../bootstrap.php";

function buildState(array $list, string $isin, string $name, string $priceType, Date $date, float $price)
{
    /**
     * @var $state State|null
     */
    $state = $list[sprintf("%s:%s", $isin, $priceType)] ?? null;

    if ($state === null) {
        $state = new State;
        $state->setIsin($isin);
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

    $state->setName($name);
    $state->setUpdated($date);
    $state->setPrice($price);

    return $state;
}

$entityManager = Database::getEntityManager();
$watchListEntryRepository = $entityManager->getRepository(WatchListEntry::class);
$watchLists = $entityManager->getRepository(WatchList::class)->findAll();

$isinList = [];

foreach ($watchLists as $watchList) {
    /**
     * @var $entry WatchListEntry
     */
    foreach ($watchList->getEntries() as $entry) {
        $isinList[] = $entry->getIsin();
    }
}

$isinList = array_filter(array_unique($isinList));

$fetcher = new Fetcher;

foreach ($isinList as $isin) {
    $fetcher->add($isin);
}

$responseDataList = $fetcher->execute();

$allStates = [];

foreach ($entityManager->getRepository(State::class)->findAll() as $state) {
    /**
     * @var $isin string
     */
    $isin = $state->getIsin();

    $key = sprintf("%s:%s", $isin, $state->getPriceType());

    if (!in_array($isin, $isinList)) {
        $entityManager->remove($state);
        continue;
    }

    $allStates[$key] = $state;
}

$entityManager->flush();

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

        $bidState = buildState($allStates, $responseData->isin, $responseData->name, PriceType::BID, $responseData->bidDate, $responseData->bidPrice);
        $askState = buildState($allStates, $responseData->isin, $responseData->name, PriceType::ASK, $responseData->askDate, $responseData->askPrice);

        $entityManager->persist($bidState);
        $entityManager->persist($askState);
        $entityManager->flush();

        $watchListEntries = $watchListEntryRepository->findByIsin($responseData->isin);
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

            $entityManager->persist($entry);
        }
        $entityManager->flush();
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