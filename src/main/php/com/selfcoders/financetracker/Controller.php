<?php
namespace com\selfcoders\financetracker;

use com\selfcoders\financetracker\fetcher\Fetcher;
use com\selfcoders\financetracker\models\News;
use com\selfcoders\financetracker\models\State;
use com\selfcoders\financetracker\models\WatchList;
use com\selfcoders\financetracker\models\WatchListEntry;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

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

        $watchLists = $entityManager->getRepository(WatchList::class)->findBy([], ["name" => "asc"]);

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

    public function getOriginalName(array $params)
    {
        $fetcher = new Fetcher;

        $fetcher->add($params["isin"]);

        $responses = $fetcher->execute();

        if (empty($responses)) {
            http_response_code(404);
        } else {
            header("Content-Type: text/plain");
            echo array_values($responses)[0]->name;
        }
    }

    public function updateEntry(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchListRepository = $entityManager->getRepository(WatchList::class);
        $watchListEntryRepository = $entityManager->getRepository(WatchListEntry::class);
        $stateRepository = $entityManager->getRepository(State::class);

        $watchListEntry = $watchListEntryRepository->findByListAndIsin($params["name"], $params["isin"]);

        if ($watchListEntry === null) {
            $watchListEntry = new WatchListEntry;

            $watchListEntry->setIsin($params["isin"]);
            $watchListEntry->setWatchList($watchListRepository->findByName($params["name"]));
            $watchListEntry->setState($stateRepository->findByIsin($params["isin"]));
        }

        $watchListEntry->setName($_POST["name"]);
        $watchListEntry->setCount(floatval($_POST["count"]));
        $watchListEntry->setPrice(floatval($_POST["price"]));
        $watchListEntry->setDate(new Date($_POST["date"]));
        $watchListEntry->setLimitEnabled(filter_var($_POST["limitEnabled"], FILTER_VALIDATE_BOOLEAN));
        $watchListEntry->setLowLimit(floatval($_POST["lowLimit"]));
        $watchListEntry->setHighLimit(floatval($_POST["highLimit"]));
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
            $this->buildNewsList($news, $allItems);
        }

        usort($allItems, function ($item1, $item2) {
            return $item2["date"]->getTimestamp() - $item1["date"]->getTimestamp();
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

        $allItems = [];

        $this->buildNewsList($news, $allItems);

        usort($allItems, function ($item1, $item2) {
            return $item2["date"]->getTimestamp() - $item1["date"]->getTimestamp();
        });

        header("Content-Type: application/json");
        header("Content-Type: application/json");
        echo json_encode($allItems);
    }

    public function getNewsHtmlForEntry(array $params)
    {
        $entityManager = Database::getEntityManager();

        $news = $entityManager->getRepository(News::class)->findByIsin($params["isin"]);
        if ($news === null) {
            http_response_code(404);
            return;
        }

        $allItems = [];

        $this->buildNewsList($news, $allItems);

        usort($allItems, function ($item1, $item2) {
            return $item2["date"]->getTimestamp() - $item1["date"]->getTimestamp();
        });

        echo TwigRenderer::render("news", [
            "news" => $allItems
        ]);
    }

    private function buildNewsList(News $news, array &$allItems)
    {
        $state = Database::getEntityManager()->getRepository(State::class)->findByIsin($news->getIsin());

        $currentPrice = $state?->getPrice();
        $dayStartPrice = $state?->getDayStartPrice();

        foreach ($news->getItems() as $item) {
            $allItems[] = [
                "name" => $news->getName(),
                "title" => $item->title,
                "url" => $item->url,
                "date" => $item->date,
                "currentPrice" => $currentPrice,
                "dayStartPrice" => $dayStartPrice
            ];
        }
    }

    public function grafanaSearch()
    {
        $entityManager = Database::getEntityManager();

        $watchListEntries = $entityManager->getRepository(WatchListEntry::class)->findAll();

        $json = [];

        foreach ($watchListEntries as $watchListEntry) {
            $json[] = [
                "text" => $watchListEntry->getName(),
                "value" => $watchListEntry->getIsin()
            ];
        }

        header("Content-Type: application/json");
        echo json_encode($json);
    }

    public function grafanaQuery()
    {
        $json = json_decode(file_get_contents("php://input"), true);

        $client = new Client([
            "base_uri" => "https://component-api.wertpapiere.ing.de/api/v1/components/charttooldata/",
            RequestOptions::QUERY => [
                "timeRange" => "Intraday",
                "exchangeId" => 2779,
                "currencyId" => 814
            ]
        ]);

        $requests = [];
        $responses = [];

        $entityManager = Database::getEntityManager();

        $watchListEntries = $entityManager->getRepository(WatchListEntry::class)->findAll();

        $nameMap = [];

        foreach ($watchListEntries as $watchListEntry) {
            /**
             * @var $isin string
             */
            $isin = $watchListEntry->getIsin();

            $nameMap[$isin] = $watchListEntry->getName();
        }

        $isins = $json["targets"][0]["data"]["isins"];

        if (is_array($isins)) {
            foreach ($isins as $isin) {
                $requests[$isin] = new Request("GET", $isin);
            }
        } else {
            $requests[$isins] = new Request("GET", $isins);
        }

        $pool = new Pool($client, $requests, [
            "concurrency" => 10,
            "fulfilled" => function (Response $response, string $isin) use (&$responses, $nameMap) {
                $json = json_decode($response->getBody(), true);

                $data = [];

                foreach ($json["instruments"][0]["data"] as $item) {
                    $data[] = [$item[1], $item[0]];
                }

                $responses[] = [
                    "target" => $nameMap[$isin] ?? $isin,
                    "datapoints" => $data
                ];
            }
        ]);

        $pool->promise()->wait();

        header("Content-Type: application/json");
        echo json_encode($responses);
    }

    public function emptyResponse()
    {
    }
}