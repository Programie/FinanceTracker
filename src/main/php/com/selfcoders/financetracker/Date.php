<?php
namespace com\selfcoders\financetracker;

use DateInterval;
use DateTime;
use JsonSerializable;

class Date extends DateTime implements JsonSerializable
{
    public function isWeekend(): bool
    {
        $weekday = $this->format("N");

        // Saturday or Sunday
        return $weekday == 6 or $weekday == 7;
    }

    public function isInTimeRange(string $startTime, string $endTime, int $tolerance = 0): bool
    {
        $startDate = new static($startTime);
        $endDate = new static($endTime);

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