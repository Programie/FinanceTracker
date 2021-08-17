<?php
namespace com\selfcoders\financetracker;

use DateInterval;
use DateTime as BaseDateTime;
use DateTimeZone;
use JsonSerializable;

class DateTime extends BaseDateTime implements JsonSerializable
{
    public static function fromUtc(string $datetime): DateTime
    {
        return new self($datetime, new DateTimeZone("UTC"));
    }

    public function toUtc(): DateTime
    {
        $utcDate = clone $this;
        $utcDate->setTimezone(new DateTimeZone("UTC"));
        return $utcDate;
    }

    public function formatRelativeTime(): string
    {
        $diff = (new self)->getTimestamp() - $this->getTimestamp();

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

    public function isWeekend(): bool
    {
        $weekday = $this->format("N");

        // Saturday or Sunday
        return $weekday == 6 or $weekday == 7;
    }

    public function isInTimeRange(string $startTime, string $endTime, int $tolerance = 0): bool
    {
        $startDate = new self($startTime);
        $endDate = new self($endTime);

        $startDate->sub(new DateInterval(sprintf("PT%dS", $tolerance)));
        $endDate->add(new DateInterval(sprintf("PT%dS", $tolerance)));

        // Is multi day range? (e.g. 17:00 - 08:00)
        if ($endDate < $startDate) {
            $endDate = clone $endDate;

            $endDate->modify("+1 day");
        }

        return ($this >= $startDate and $this <= $endDate);
    }

    public function jsonSerialize()
    {
        return $this->format("c");
    }
}