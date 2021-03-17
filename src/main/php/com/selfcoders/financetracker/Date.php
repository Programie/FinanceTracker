<?php
namespace com\selfcoders\financetracker;

use DateTime;
use JsonSerializable;

class Date extends DateTime implements JsonSerializable
{
    public function jsonSerialize()
    {
        return $this->format("c");
    }
}