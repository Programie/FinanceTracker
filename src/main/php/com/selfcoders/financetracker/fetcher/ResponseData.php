<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\Date;

class ResponseData
{
    public ?string $isin = null;
    public ?string $wkn = null;
    public ?string $name = null;
    public ?float $bidPrice = null;
    public ?float $askPrice = null;
    public ?Date $bidDate = null;
    public ?Date $askDate = null;
    public Date $fetchDate;

    public function __construct(Date $fetchDate)
    {
        $this->fetchDate = $fetchDate;
    }
}