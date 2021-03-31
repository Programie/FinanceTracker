<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\Date;

class ResponseData
{
    public string $isin;
    public ?string $name = null;
    public ?float $bidPrice = null;
    public ?float $askPrice = null;
    public ?Date $bidDate = null;
    public ?Date $askDate = null;
}