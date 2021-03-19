<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\Date;

class ResponseData
{
    public string $isin;
    public ?string $name = null;
    public ?float $price = null;
    public ?Date $date = null;
}