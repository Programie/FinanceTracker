<?php
namespace com\selfcoders\financetracker\models;

use com\selfcoders\financetracker\DateTime;

class NewsItem
{
    public string $title;
    public ?string $url;
    public DateTime $date;

    public static function fromArray(array $data): NewsItem
    {
        $item = new self;

        $item->title = $data["title"];
        $item->url = $data["url"];
        $item->date = new DateTime($data["date"]);

        return $item;
    }
}