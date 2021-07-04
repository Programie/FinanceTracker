<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\Date;
use DOMDocument;
use DOMElement;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;

class LSFetcher extends BaseFetcher
{
    private Client $client;
    private array $wknIsinMap = [];

    public function __construct()
    {
        $this->client = new Client([
            RequestOptions::TIMEOUT => 5
        ]);
    }

    public function add(string $isin, ?string $wkn)
    {
        $this->wknIsinMap[$wkn] = $isin;
    }

    public function execute()
    {
        $startDate = new Date;
        $responseDataList = [];

        $response = $this->client->get("https://www.ls-tc.de/de/watchlist", [
            RequestOptions::COOKIES => CookieJar::fromArray([
                "watchlist" => implode("%2C", array_keys($this->wknIsinMap))
            ], "www.ls-tc.de")
        ]);

        $doc = new DOMDocument;
        $doc->loadHTML($response->getBody()->getContents(), LIBXML_NOERROR);

        $xpath = new DOMXPath($doc);
        /**
         * @var DOMElement $tableRow
         */
        foreach ($xpath->query("//table/tbody/tr") as $tableRow) {
            $cells = $tableRow->getElementsByTagName("td");

            $responseData = new ResponseData($startDate);

            $responseData->wkn = $cells->item(0)?->nodeValue;
            $responseData->isin = $this->wknIsinMap[$responseData->wkn] ?? null;
            $responseData->name = $cells->item(1)?->nodeValue;
            $responseData->bidPrice = self::parsePrice($cells->item(2)?->nodeValue);
            $responseData->askPrice = self::parsePrice($cells->item(3)?->nodeValue);

            $time = $cells->item(6)?->nodeValue ?? null;

            if ($time !== null and preg_match("/^([0-9]{2}:[0-9]{2}:[0-9]{2})$/", $time)) {
                $date = new Date($time);
            } else {
                $date = new Date;
            }

            $responseData->bidDate = $date;
            $responseData->askDate = $date;

            if ($responseData->isin === null or $responseData->bidPrice === null or $responseData->askPrice === null) {
                fwrite(STDERR, sprintf("[%s] Error while getting data from LS watchlist entry: %s\n", date("r"), $tableRow->nodeValue));
                continue;
            }

            $responseDataList[$responseData->isin] = $responseData;
        }

        return $responseDataList;
    }

    private static function parsePrice($string): float|null
    {
        if ($string === null) {
            return null;
        }

        $string = str_replace(".", "", $string);
        $string = str_replace(",", ".", $string);

        return floatval($string);
    }
}