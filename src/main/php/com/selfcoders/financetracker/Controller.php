<?php
namespace com\selfcoders\financetracker;

use com\selfcoders\financetracker\fetcher\Fetcher;
use com\selfcoders\financetracker\models\News;
use com\selfcoders\financetracker\models\NewsItem;
use com\selfcoders\financetracker\models\WatchList;
use com\selfcoders\financetracker\models\WatchListEntry;

class Controller
{
    public function redirectToDefaultWatchlist()
    {
        $entityManager = Database::getEntityManager();

        $watchList = $entityManager->getRepository(WatchList::class)->findByName("Watchlist");
        if ($watchList === null) {
            $watchList = $entityManager->getRepository(WatchList::class)->findAll()[0];
        }

        header(sprintf("Location: /watchlist/%s", $watchList->getName()));
    }

    public function getContent(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchLists = $entityManager->getRepository(WatchList::class)->findAll();

        $watchList = $entityManager->getRepository(WatchList::class)->findByName($params["name"]);
        if ($watchList === null) {
            http_response_code(404);
            return;
        }

        echo TwigRenderer::render("page", [
            "watchLists" => $watchLists,
            "watchList" => $watchList
        ]);
    }

    public function getJson(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchList = $entityManager->getRepository(WatchList::class)->findByName($params["name"]);
        if ($watchList === null) {
            http_response_code(404);
            return;
        }

        $json = [];

        foreach ($watchList->getEntries() as $watchListEntry) {
            $json[] = $watchListEntry->jsonSerialize();
        }

        header("Content-Type: application/json");
        echo json_encode($json);
    }

    public function getCurrentPrice(array $params)
    {
        $fetcher = new Fetcher;

        $fetcher->add($params["isin"]);

        $responses = $fetcher->execute();

        if (empty($responses)) {
            http_response_code(404);
        } else {
            header("Content-Type: text/plain");
            echo array_values($responses)[0]->price;
        }
    }

    public function updateEntry(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchListEntryRepository = $entityManager->getRepository(WatchListEntry::class);

        $watchListEntry = $watchListEntryRepository->findByListAndIsin($params["name"], $params["isin"]);

        if ($watchListEntry === null) {
            $watchListEntry = new WatchListEntry;

            $watchListEntry->setIsin($params["isin"]);
        }

        $watchListEntry->setName($_POST["name"]);
        $watchListEntry->setCount(floatval($_POST["count"]));
        $watchListEntry->setPrice(floatval($_POST["price"]));
        $watchListEntry->setDate(new Date($_POST["date"]));
        $watchListEntry->setLimitEnabled(filter_var($_POST["limitEnabled"], FILTER_VALIDATE_BOOLEAN));
        $watchListEntry->setLimitType($_POST["limitType"]);
        $watchListEntry->setLimit(floatval($_POST["limit"]));
        $watchListEntry->setNewsEnabled(filter_var($_POST["newsEnabled"], FILTER_VALIDATE_BOOLEAN));

        $entityManager->persist($watchListEntry);
        $entityManager->flush();
    }

    public function removeEntry(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchListEntryRepository = $entityManager->getRepository(WatchListEntry::class);

        $watchListEntry = $watchListEntryRepository->findByListAndIsin($params["name"], $params["isin"]);

        if ($watchListEntry === null) {
            http_response_code(404);
            return;
        }

        $entityManager->remove($watchListEntry);
        $entityManager->flush();
    }

    public function resetNotified(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchListEntryRepository = $entityManager->getRepository(WatchListEntry::class);

        $watchListEntry = $watchListEntryRepository->findByListAndIsin($params["name"], $params["isin"]);

        if ($watchListEntry === null) {
            http_response_code(404);
            return;
        }

        $watchListEntry->setNotified(false);

        $entityManager->persist($watchListEntry);
        $entityManager->flush();
    }

    public function getNews()
    {
        $entityManager = Database::getEntityManager();

        $allItems = [];

        foreach ($entityManager->getRepository(News::class)->findAll() as $news) {
            foreach ($news->getItems() as $item) {
                $allItems[] = $item;
            }
        }

        usort($allItems, function (NewsItem $item1, NewsItem $item2) {
            return $item2->date->getTimestamp() - $item1->date->getTimestamp();
        });

        header("Content-Type: application/json");
        header("Content-Type: application/json");
        echo json_encode($allItems);
    }

    public function getNewsForEntry(array $params)
    {
        $entityManager = Database::getEntityManager();

        $news = $entityManager->getRepository(News::class)->findByIsin($params["isin"]);
        if ($news === null) {
            http_response_code(404);
            return;
        }

        $items = $news->getItems();

        usort($items, function (NewsItem $item1, NewsItem $item2) {
            return $item2->date->getTimestamp() - $item1->date->getTimestamp();
        });

        header("Content-Type: application/json");
        header("Content-Type: application/json");
        echo json_encode($items);
    }
}