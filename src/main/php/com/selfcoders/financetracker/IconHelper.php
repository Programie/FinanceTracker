<?php
namespace com\selfcoders\financetracker;

class IconHelper
{
    private static ?array $coinMap = null;

    public static function updateCoinMap(string $jsonFile)
    {
        $coinList = json_decode(file_get_contents($jsonFile), true);
        $map = [];

        foreach ($coinList as $entry) {
            $map[$entry["symbol"]] = $entry["slug"];
        }

        file_put_contents(RESOURCES_ROOT . "/coinmap.bin", serialize($map));
    }

    private static function getCoinMap()
    {
        if (self::$coinMap === null) {
            self::$coinMap = unserialize(file_get_contents(RESOURCES_ROOT . "/coinmap.bin"));
        }

        return self::$coinMap;
    }

    public static function getIcon(string $isin)
    {
        if (str_starts_with($isin, "CRYPTO:") or str_starts_with($isin, "CMC:")) {
            $name = explode(":", $isin, 2)[1];
            $map = self::getCoinMap();

            if (!isset($map[$name])) {
                return null;
            }

            return sprintf("https://raw.githubusercontent.com/ErikThiart/cryptocurrency-icons/master/64/%s.png", self::getCoinMap()[$name]);
        } else {
            return sprintf("https://assets.traderepublic.com/img/logos/%s/dark.svg", $isin);
        }
    }
}