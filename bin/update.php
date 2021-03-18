#! /usr/bin/env php
<?php
use com\selfcoders\financetracker\Database;
use com\selfcoders\financetracker\fetcher\Fetcher;
use com\selfcoders\financetracker\models\State;
use com\selfcoders\financetracker\models\WatchList;
use com\selfcoders\financetracker\models\WatchListEntry;
use com\selfcoders\financetracker\NotificationRecipient;

require_once __DIR__ . "/../bootstrap.php";

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

    if (!in_array($isin, $isinList)) {
        $entityManager->remove($state);
        continue;
    }

    $allStates[$isin] = $state;
}

$entityManager->flush();

/**
 * @var $newNotifications WatchListEntry
 */
$newNotifications = [];

try {
    foreach ($responseDataList as $responseData) {
        $state = $allStates[$responseData->isin] ?? null;

        if ($state === null) {
            $state = new State;
            $state->setIsin($responseData->isin);
        }

        $state->setName($responseData->name);
        $state->setUpdated($responseData->date);
        $state->setPrice($responseData->price);

        $entityManager->persist($state);
        $entityManager->flush();

        $watchListEntries = $watchListEntryRepository->findByIsin($responseData->isin);
        foreach ($watchListEntries as $entry) {
            $entry->setState($state);

            if ($entry->hasReachedLimit() and !$entry->isNotified()) {
                $entry->setNotified(true);

                $newNotifications[] = $entry;
            }

            $entityManager->persist($entry);
        }
        $entityManager->flush();
    }
} finally {
    $notificationsPerRecipient = [];

    foreach ($newNotifications as $entry) {
        $recipients = $entry->getWatchList()->getNotificationRecipients();

        foreach ($recipients as $recipient) {
            $recipientKey = $recipient->__toString();
            if (!isset($notificationsPerRecipient[$recipientKey])) {
                $notificationsPerRecipient[$recipientKey] = [
                    "recipient" => $recipient,
                    "entries" => []
                ];
            }

            $notificationsPerRecipient[$recipientKey]["entries"][] = $entry;
        }
    }

    foreach ($notificationsPerRecipient as $notification) {
        /**
         * @var $recipient NotificationRecipient
         */
        $recipient = $notification["recipient"];
        $entries = $notification["entries"];

        try {
            $recipient->sendForWatchListEntries($entries);
        } catch (Exception $exception) {
            echo $exception;
        }
    }
}