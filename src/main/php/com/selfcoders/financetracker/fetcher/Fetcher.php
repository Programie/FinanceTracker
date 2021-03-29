<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\Date;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class Fetcher
{
    private Client $client;
    private array $requests = [];

    public function __construct()
    {
        $this->client = new Client;
    }

    public function add(string $isin)
    {
        if ($isin === "BTC") {
            $url = "https://api.coindesk.com/v1/bpi/currentprice/EUR.json";
        } else {
            $url = sprintf("https://component-api.wertpapiere.ing.de/api/v1/components/instrumentheader/%s", $isin);
        }

        $this->requests[$isin] = new Request("GET", $url);
    }

    /**
     * @return ResponseData[]
     */
    public function execute()
    {
        $responseDataList = [];

        $pool = new Pool($this->client, $this->requests, [
            "concurrency" => 10,
            "fulfilled" => function (Response $response, string $isin) use (&$responseDataList) {
                $json = json_decode($response->getBody(), true);

                $responseData = new ResponseData;
                $responseData->isin = $isin;

                if ($isin === "BTC") {
                    $responseData->name = "Bitcoin";
                    $responseData->price = $json["bpi"]["EUR"]["rate_float"] ?? null;

                    $date = $json["time"]["updatedISO"] ?? null;
                    if ($date !== null) {
                        $responseData->date = new Date($date);
                    }
                } else {
                    $responseData->name = $json["name"] ?? null;
                    $responseData->price = $json["price"] ?? null;

                    $date = $json["priceChangeDate"] ?? null;
                    if ($date !== null) {
                        $responseData->date = new Date($date);
                    }
                }

                $responseDataList[$isin] = $responseData;
            }
        ]);

        $pool->promise()->wait();

        return $responseDataList;
    }
}