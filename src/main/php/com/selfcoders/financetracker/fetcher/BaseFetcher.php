<?php
namespace com\selfcoders\financetracker\fetcher;

abstract class BaseFetcher implements Fetcher
{
    public const DATASOURCE_ING = "ing";
    public const DATASOURCE_LS = "ls";

    public static function getFetcher(string $isin, string $dataSource = BaseFetcher::DATASOURCE_ING): BaseFetcher|null
    {
        if (str_starts_with($isin, "CRYPTO:")) {
            return new CryptoFetcher;
        } elseif ($dataSource === BaseFetcher::DATASOURCE_ING) {
            return new INGFetcher;
        } elseif ($dataSource === BaseFetcher::DATASOURCE_LS) {
            return new LSFetcher;
        } else {
            return null;
        }
    }
}