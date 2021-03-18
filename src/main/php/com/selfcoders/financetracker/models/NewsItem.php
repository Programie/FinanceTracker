<?php
namespace com\selfcoders\financetracker\models;

use com\selfcoders\financetracker\Date;

class NewsItem
{
    public string $title;
    public ?string $url;
    public Date $date;

    public static function fromArray(array $data): NewsItem
    {
        $item = new self;

        $item->title = $data["title"];
        $item->url = $data["url"];
        $item->date = new Date($data["date"]);

        return $item;
    }
}