<?php
namespace com\selfcoders\financetracker\fetcher;

class FetcherHelper
{
    /**
     * @param array $isinWknList
     * @param bool $force
     * @return ResponseData[]
     */
    public static function getData(array $isinWknList, bool $force = false): array
    {
        $cryptoFetcher = new CryptoFetcher;
        $lsFetcher = new LSFetcher;

        foreach ($isinWknList as $isinWknEntry) {
            list($isin, $wkn) = $isinWknEntry;

            if (str_starts_with($isin, "CRYPTO:")) {
                $cryptoFetcher->add($isin, $wkn);
            } else {
                $lsFetcher->add($isin, $wkn);
            }
        }

        return array_merge($cryptoFetcher->execute($force), $lsFetcher->execute($force));
    }
}