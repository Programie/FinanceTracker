<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class INGFetcher extends BaseFetcher
{
    private Client $client;
    private array $requests = [];

    public function __construct()
    {
        $this->client = new Client([
            "base_uri" => "https://component-api.wertpapiere.ing.de/api/v1/components/instrumentheader/",
            RequestOptions::TIMEOUT => 5
        ]);
    }

    public function add(string $isin, ?string $wkn): void
    {
        $this->requests[$isin] = new Request("GET", $isin);
    }

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
                $responseData->wkn = $json["wkn"] ?? null;
                $responseData->name = $json["name"] ?? null;
                $responseData->bidPrice = $json["bid"] ?? null;
                $responseData->askPrice = $json["ask"] ?? null;
                $responseData->bidDate = $this->dateOrNull($json["bidDate"] ?? null);
                $responseData->askDate = $this->dateOrNull($json["askDate"] ?? null);

                $responseDataList[$isin] = $responseData;
            },
            "rejected" => function (Exception $reason, string $isin) {
                fwrite(STDERR, sprintf("[%s] Error while getting data from ING API for %s: %s\n", date("r"), $isin, $reason->getMessage()));
            }
        ]);

        $pool->promise()->wait();

        return $responseDataList;
    }

    public static function shouldUpdate(int $tolerance): bool
    {
        $now = new DateTime;

        if ($now->isWeekend()) {
            return false;
        }

        if (!$now->isInTimeRange("08:00:00", "22:00:00", $tolerance)) {
            return false;
        }

        return true;
    }

    private function dateOrNull($datetime): ?DateTime
    {
        if ($datetime === null) {
            return null;
        }

        return new DateTime($datetime);
    }
}