<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\DateTime;

class ResponseData
{
    public ?string $isin = null;
    public ?string $wkn = null;
    public ?string $name = null;
    public ?float $bidPrice = null;
    public ?float $askPrice = null;
    public ?DateTime $bidDate = null;
    public ?DateTime $askDate = null;
    public DateTime $fetchDate;

    public function __construct(DateTime $fetchDate)
    {
        $this->fetchDate = $fetchDate;
    }
}