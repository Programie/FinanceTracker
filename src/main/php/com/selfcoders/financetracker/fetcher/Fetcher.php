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
        if (str_starts_with($isin, "BITPANDA:")) {
            $url = "https://api.bitpanda.com/v1/ticker";
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

                if (str_starts_with($isin, "BITPANDA:")) {
                    $realIsin = trim(substr($isin, 9));

                    $responseData->name = $realIsin;
                    $price = $json[$realIsin]["EUR"] ?? null;
                    if ($price !== null) {
                        $price = floatval($price);
                    }

                    $responseData->bidPrice = $price;
                    $responseData->askPrice = $price;
                    $responseData->bidDate = new Date;
                    $responseData->askDate = $responseData->bidDate;
                } else {
                    $responseData->name = $json["name"] ?? null;
                    $responseData->bidPrice = $json["bid"] ?? null;
                    $responseData->askPrice = $json["ask"] ?? null;
                    $responseData->bidDate = $this->dateOrNull($json["bidDate"] ?? null);
                    $responseData->askDate = $this->dateOrNull($json["askDate"] ?? null);
                }

                $responseDataList[$isin] = $responseData;
            }
        ]);

        $pool->promise()->wait();

        return $responseDataList;
    }

    private function dateOrNull($datetime): ?Date
    {
        if ($datetime === null) {
            return null;
        }

        return new Date($datetime);
    }
}