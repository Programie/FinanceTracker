<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\Date;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class CryptoFetcher extends BaseFetcher
{
    private Client $client;
    private array $requests = [];

    public function __construct()
    {
        $this->client = new Client([
            "base_uri" => "https://api.binance.com"
        ]);
    }

    public function add(string $isin, ?string $wkn)
    {
        $this->requests[$isin] = new Request("GET", sprintf("api/v3/ticker/price?symbol=%sEUR", strtoupper(trim(substr($isin, 7)))));
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

                $responseData->name = trim(substr($isin, 7));
                $price = $json["price"] ?? null;
                if ($price !== null) {
                    $price = floatval($price);
                }

                $responseData->bidPrice = $price;
                $responseData->askPrice = $price;
                $responseData->bidDate = new Date;
                $responseData->askDate = $responseData->bidDate;

                $responseDataList[$isin] = $responseData;
            }
        ]);

        $pool->promise()->wait();

        return $responseDataList;
    }
}