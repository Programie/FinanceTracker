<?php
namespace com\selfcoders\financetracker;

use DateTime;
use JsonSerializable;

class Date extends DateTime implements JsonSerializable
{
    public function formatRelativeTime()
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

    public function jsonSerialize()
    {
        return $this->format("c");
    }
}