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
        $coinMarketCapFetcher = new CoinMarketCapFetcher;
        $defaultFetcher = new INGFetcher;

        foreach ($isinWknList as $isinWknEntry) {
            list($isin, $wkn) = $isinWknEntry;

            if (str_starts_with($isin, BaseFetcher::ISIN_PREFIX_CRYPTO)) {
                $cryptoFetcher->add($isin, $wkn);
            } elseif (str_starts_with($isin, BaseFetcher::ISIN_PREFIX_COINMARKETCAP)) {
                $coinMarketCapFetcher->add($isin, $wkn);
            } else {
                $defaultFetcher->add($isin, $wkn);
            }
        }

        return array_merge($cryptoFetcher->execute($force), $coinMarketCapFetcher->execute($force), $defaultFetcher->execute($force));
    }
}