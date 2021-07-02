<?php
namespace com\selfcoders\financetracker\fetcher;

interface Fetcher
{
    public function add(string $isin, ?string $wkn);

    /**
     * @return ResponseData[]
     */
    public function execute();
}