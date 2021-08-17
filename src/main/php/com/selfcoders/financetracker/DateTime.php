<?php
namespace com\selfcoders\financetracker;

use DateTimeZone;

class DateTime extends Date
{
    public static function fromUtc(string $datetime): DateTime
    {
        return new static($datetime, new DateTimeZone("UTC"));
    }

    public function toUtc(): DateTime
    {
        $utcDate = clone $this;
        $utcDate->setTimezone(new DateTimeZone("UTC"));
        return $utcDate;
    }

    public function formatRelativeTime(): string
    {
        $diff = (new static)->getTimestamp() - $this->getTimestamp();

        if ($diff < 60) {
            return "just now";
        }

        if ($diff < 120) {
            return "1m ago";
        }

        if ($diff < 3600) {
            return sprintf("%dm ago", floor($diff / 60));
        }

        if ($diff < 7200) {
            return "1h ago";
        }

        return sprintf("%dh ago", floor($diff / 3600));
    }
}