<?php
namespace com\selfcoders\financetracker;

use com\selfcoders\financetracker\fetcher\BaseFetcher;
use com\selfcoders\financetracker\fetcher\ResponseData;
use com\selfcoders\financetracker\models\News;
use com\selfcoders\financetracker\models\State;
use com\selfcoders\financetracker\models\WatchList;
use com\selfcoders\financetracker\models\WatchListEntry;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use RuntimeException;

class Controller
{
    public function redirectToDefaultWatchlist()
    {
        $entityManager = Database::getEntityManager();

        $watchList = $entityManager->getRepository(WatchList::class)->findByName("Watchlist");
        if ($watchList === null) {
            $watchList = $entityManager->getRepository(WatchList::class)->findEnabled()[0];
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
        $fetcher = BaseFetcher::getFetcher($params["isin"]);

        $fetcher->add($params["isin"], null);

        $responses = $fetcher->execute(true);

        if (empty($responses)) {
            http_response_code(404);
        } else {
            /**
             * @var $responseData ResponseData
             */
            $responseData = array_values($responses)[0];

            $priceType = $_GET["type"] ?? PriceType::BID;
            if ($priceType === PriceType::ASK) {
                $price = $responseData->askPrice;
            } else {
                $price = $responseData->bidPrice;
            }

            header("Content-Type: text/plain");
            echo $price;
        }
    }

    public function getOriginalName(array $params)
    {
        $fetcher = BaseFetcher::getFetcher($params["isin"]);

        $fetcher->add($params["isin"], null);

        $responses = $fetcher->execute(true);

        if (empty($responses)) {
            http_response_code(404);
        } else {
            header("Content-Type: text/plain");
            echo array_values($responses)[0]->name;
        }
    }

    public function createEntry(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchListRepository = $entityManager->getRepository(WatchList::class);
        $stateRepository = $entityManager->getRepository(State::class);

        $watchListEntry = new WatchListEntry;

        $watchList = $watchListRepository->findByName($params["name"]);

        $watchListEntry->updateFromData($_POST);

        $watchListEntry->setWatchList($watchList);
        $watchListEntry->setState($stateRepository->findByIsinAndPriceType($watchListEntry->getIsin(), $watchList->getPriceType()));

        $entityManager->persist($watchListEntry);
        $entityManager->flush();

        header("Content-Type: text/plain");
        echo $watchListEntry->getId();
    }

    public function updateEntry(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchListEntryRepository = $entityManager->getRepository(WatchListEntry::class);

        $watchListEntry = $watchListEntryRepository->findByListAndId($params["name"], (int)$params["id"]);
        if ($watchListEntry === null) {
            http_response_code(404);
            return;
        }

        $watchListEntry->updateFromData($_POST);

        $entityManager->persist($watchListEntry);
        $entityManager->flush();

        header("Content-Type: text/plain");
        echo $watchListEntry->getId();
    }

    public function removeEntry(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchListEntryRepository = $entityManager->getRepository(WatchListEntry::class);

        $watchListEntry = $watchListEntryRepository->findByListAndId($params["name"], (int)$params["id"]);
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

        $watchListEntry = $watchListEntryRepository->findByListAndId($params["name"], (int)$params["id"]);
        if ($watchListEntry === null) {
            http_response_code(404);
            return;
        }

        $watchListEntry->clearNotification();

        $entityManager->persist($watchListEntry);
        $entityManager->flush();
    }

    public function toggleNotifications(array $params)
    {
        $entityManager = Database::getEntityManager();

        $watchList = $entityManager->getRepository(WatchList::class)->findByName($params["name"]);
        if ($watchList === null) {
            http_response_code(404);
            return;
        }

        $watchList->setNotificationsEnabled(filter_var($_POST["state"], FILTER_VALIDATE_BOOLEAN));

        $entityManager->persist($watchList);
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
        $state = Database::getEntityManager()->getRepository(State::class)->findByIsinAndPriceType($news->getIsin(), PriceType::BID);

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
        $json = json_decode(file_get_contents("php://input"), true);

        $entityManager = Database::getEntityManager();
        $watchListRepository = $entityManager->getRepository(WatchListEntry::class);

        $watchListName = $json["target"] ?? null;
        if ($watchListName === null) {
            $watchListEntries = $watchListRepository->findAll();
        } else {
            $watchListEntries = $watchListRepository->findByList($watchListName);
        }

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

        $watchListName = $json["targets"][0]["data"]["list"] ?? null;

        if ($watchListName === null) {
            $watchListEntries = $entityManager->getRepository(WatchListEntry::class)->findAll();

            $isins = $json["targets"][0]["data"]["isins"];
        } else {
            $watchListEntries = $entityManager->getRepository(WatchListEntry::class)->findByList($watchListName);

            $isins = [];
            foreach ($watchListEntries as $watchListEntry) {
                $isins[] = $watchListEntry->getIsin();
            }
        }

        $nameMap = [];

        foreach ($watchListEntries as $watchListEntry) {
            /**
             * @var $isin string
             */
            $isin = $watchListEntry->getIsin();

            $nameMap[$isin] = $watchListEntry->getName();
        }

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

    public function redirectToCoinMarketCap(array $params)
    {
        $symbol = strtoupper($params["symbol"]);

        $apiKeys = explode(",", getenv("COINMARKETCAP_API_KEY"));

        $client = new Client([
            "base_uri" => "https://pro-api.coinmarketcap.com",
            RequestOptions::HEADERS => [
                "X-CMC_PRO_API_KEY" => $apiKeys[array_rand($apiKeys)]
            ]
        ]);

        $response = $client->get("/v1/cryptocurrency/info", [
            RequestOptions::QUERY => [
                "symbol" => $symbol
            ]
        ]);

        $json = json_decode($response->getBody(), true);

        $slug = $json["data"][$symbol]["slug"] ?? null;

        if ($slug === null) {
            throw new RuntimeException("No slug returned in request to CoinMarketCap API!");
        }

        header(sprintf("Location: https://coinmarketcap.com/currencies/%s/", $slug));
    }

    public function emptyResponse()
    {
    }
}