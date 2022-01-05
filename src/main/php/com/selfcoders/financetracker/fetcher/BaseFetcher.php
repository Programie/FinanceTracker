<?php
namespace com\selfcoders\financetracker\fetcher;

abstract class BaseFetcher implements Fetcher
{
    public const ISIN_PREFIX_CRYPTO = "CRYPTO:";
    public const ISIN_PREFIX_COINMARKETCAP = "CMC:";
    public const DATASOURCE_ING = "ing";
    public const DATASOURCE_LS = "ls";

    public static function getFetcherClass(string $isin, string $dataSource = BaseFetcher::DATASOURCE_ING): ?string
    {
        if (str_starts_with($isin, self::ISIN_PREFIX_CRYPTO)) {
            return CryptoFetcher::class;
        } elseif (str_starts_with($isin, self::ISIN_PREFIX_COINMARKETCAP)) {
            return CoinMarketCapFetcher::class;
        } elseif ($dataSource === BaseFetcher::DATASOURCE_ING) {
            return INGFetcher::class;
        } elseif ($dataSource === BaseFetcher::DATASOURCE_LS) {
            return LSFetcher::class;
        } else {
            return null;
        }
    }

    public static function getFetcher(string $isin, string $dataSource = BaseFetcher::DATASOURCE_ING): BaseFetcher|null
    {
        $fetcherClass = self::getFetcherClass($isin, $dataSource);

        if ($fetcherClass === null) {
            return null;
        }

        return new $fetcherClass;
    }
}