<?php
use com\selfcoders\financetracker\Database;
use com\selfcoders\financetracker\Date;
use com\selfcoders\financetracker\models\News;
use com\selfcoders\financetracker\models\NewsItem;
use com\selfcoders\financetracker\models\WatchList;
use com\selfcoders\financetracker\models\WatchListEntry;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

require_once __DIR__ . "/../bootstrap.php";

$entityManager = Database::getEntityManager();

$watchListEntryRepository = $entityManager->getRepository(WatchListEntry::class);
$newsRepository = $entityManager->getRepository(News::class);

$isinToNames = [];

foreach ($entityManager->getRepository(WatchList::class)->findAll() as $watchList) {
    /**
     * @var $entry WatchListEntry
     */
    foreach ($watchList->getEntries() as $entry) {
        if (!$entry->isNewsEnabled()) {
            continue;
        }

        $isinToNames[$entry->getIsin()] = $entry->getName();
    }
}

$client = new Client;

$requests = [];

foreach ($isinToNames as $isin => $name) {
    $requests[$isin] = new Request("GET", sprintf("https://news.google.com/rss/search?q=%s&hl=de&gl=DE&ceid=DE:de", $name));
}

$pool = new Pool($client, $requests, [
    "concurrency" => 10,
    "fulfilled" => function (Response $response, string $isin) use ($newsRepository, $isinToNames, $entityManager) {
        $dom = new DOMDocument;
        $dom->loadXML($response->getBody());

        $xpath = new DOMXPath($dom);

        $newsObject = $newsRepository->findByIsin($isin);
        if ($newsObject === null) {
            $newsObject = new News;
            $newsObject->setIsin($isin);
        }

        $newsObject->setName($isinToNames[$isin]);

        $newsItems = [];

        /**
         * @var $item DOMElement
         */
        foreach ($xpath->query("/rss/channel/item") as $item) {
            $newsItem = new NewsItem;

            $newsItem->title = $item?->getElementsByTagName("title")?->item(0)?->nodeValue;
            $newsItem->url = $item?->getElementsByTagName("link")?->item(0)?->nodeValue;
            $date = $item?->getElementsByTagName("pubDate")?->item(0)?->nodeValue;

            if ($date !== null) {
                $newsItem->date = new Date($date);
            }

            $newsItems[] = $newsItem;
        }

        $newsObject->setItems($newsItems);

        $entityManager->persist($newsObject);
        $entityManager->flush();
    }
]);

$pool->promise()->wait();