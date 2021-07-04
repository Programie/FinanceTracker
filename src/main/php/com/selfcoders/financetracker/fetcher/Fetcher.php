<?php
namespace com\selfcoders\financetracker\fetcher;

interface Fetcher
{
    /**
     * @param string $isin
     * @param string|null $wkn
     */
    public function add(string $isin, ?string $wkn): void;

    /**
     * @param bool $force
     * @return ResponseData[]
     */
    public function execute(bool $force = false): array;

    /**
     * @param int $tolerance
     * @return bool
     */
    public static function shouldUpdate(int $tolerance): bool;
}