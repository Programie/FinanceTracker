<?php
namespace com\selfcoders\financetracker\fetcher;

use com\selfcoders\financetracker\Date;

class ResponseData
{
    public ?string $isin;
    public ?string $name;
    public ?float $price;
    public ?Date $date;
}