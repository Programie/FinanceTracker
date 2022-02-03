<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class CryptoFetcher extends BaseFetcher
{
    private Client $client;
    private array $requests = [];

    public function __construct()
    {
        $this->client = new Client([
            "base_uri" => "https://api.binance.com",
            RequestOptions::TIMEOUT => 5
        ]);
    }

    public function add(string $isin, ?string $wkn): void
    {
        $isinParts = explode(":", $isin);
        $symbol = trim(end($isinParts));

        $this->requests[$isin] = new Request("GET", sprintf("api/v3/ticker/price?symbol=%sEUR", strtoupper($symbol)));
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

                $isinParts = explode(":", $isin);
                $responseData->name = trim(end($isinParts));
                $price = $json["price"] ?? null;
                if ($price !== null) {
                    $price = floatval($price);
                }

                $responseData->bidPrice = $price;
                $responseData->askPrice = $price;
                $responseData->bidDate = new DateTime;
                $responseData->askDate = $responseData->bidDate;

                $responseDataList[$isin] = $responseData;
            },
            "rejected" => function (Exception $reason, string $isin) {
                fwrite(STDERR, sprintf("[%s] Error while getting data from Binance API for %s: %s\n", date("r"), $isin, $reason->getMessage()));
            }
        ]);

        $pool->promise()->wait();

        return $responseDataList;
    }

    public static function shouldUpdate(int $tolerance): bool
    {
        return true;
    }
}