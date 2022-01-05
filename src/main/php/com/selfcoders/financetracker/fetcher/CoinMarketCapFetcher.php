<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class CoinMarketCapFetcher extends BaseFetcher
{
    private Client $client;
    private array $requests = [];

    public function __construct()
    {
        $apiKeys = explode(",", getenv("COINMARKETCAP_API_KEY"));

        $this->client = new Client([
            "base_uri" => "https://pro-api.coinmarketcap.com",
            RequestOptions::TIMEOUT => 5,
            RequestOptions::HEADERS => [
                "X-CMC_PRO_API_KEY" => $apiKeys[array_rand($apiKeys)]
            ]
        ]);
    }

    public function add(string $isin, ?string $wkn): void
    {
        $isinParts = explode(":", $isin);
        $symbol = trim(end($isinParts));

        $this->requests[$isin] = new Request("GET", sprintf("v1/tools/price-conversion?amount=1&symbol=%s&convert=EUR", strtoupper($symbol)));
    }

    /**
     * @return ResponseData[]
     */
    public function execute(bool $force = false): array
    {
        if (!$force and !self::shouldUpdate(120)) {
            return [];
        }

        $startDate = new DateTime;
        $responseDataList = [];

        $pool = new Pool($this->client, $this->requests, [
            "concurrency" => 10,
            "fulfilled" => function (Response $response, string $isin) use (&$responseDataList, $startDate) {
                $json = json_decode($response->getBody(), true);

                $responseData = new ResponseData($startDate);
                $responseData->isin = $isin;

                $responseData->name = $json["data"]["name"] ?? null;

                $price = $json["data"]["quote"]["EUR"]["price"] ?? null;
                $date = $json["data"]["quote"]["EUR"]["last_updated"] ?? null;

                if ($price !== null) {
                    $price = floatval($price);
                }

                $responseData->bidPrice = $price;
                $responseData->askPrice = $price;
                $responseData->bidDate = new DateTime($date);
                $responseData->askDate = $responseData->bidDate;

                $responseDataList[$isin] = $responseData;
            },
            "rejected" => function (RequestException $reason, string $isin) {
                fwrite(STDERR, sprintf("[%s] Error while getting data from CoinMarketCap API for %s: %s\n", date("r"), $isin, $reason->getMessage()));
            }
        ]);

        $pool->promise()->wait();

        return $responseDataList;
    }

    public static function shouldUpdate(int $tolerance): bool
    {
        $timeRange = getenv("COINMARKETCAP_UPDATE_TIME");
        if ($timeRange === false) {
            return true;
        }

        $timeRange = trim($timeRange);
        if ($timeRange === "" or !str_contains($timeRange, "-")) {
            return true;
        }

        list($startTime, $endTime) = explode("-", $timeRange);

        $now = new DateTime;

        return $now->isInTimeRange($startTime, $endTime, $tolerance);
    }
}