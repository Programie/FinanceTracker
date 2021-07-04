<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\Date;
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

        $startDate = new Date;
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
            }
        ]);

        $pool->promise()->wait();

        return $responseDataList;
    }

    public static function shouldUpdate(int $tolerance): bool
    {
        $now = new Date;

        if ($now->isWeekend()) {
            return false;
        }

        if (!$now->isInTimeRange("08:00:00", "22:00:00", $tolerance)) {
            return false;
        }

        return true;
    }

    private function dateOrNull($datetime): ?Date
    {
        if ($datetime === null) {
            return null;
        }

        return new Date($datetime);
    }
}