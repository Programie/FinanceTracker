<?php
namespace com\selfcoders\financetracker;

use Throwable;

class Utils
{
    public static function printException(Throwable $exception)
    {
        self::logStdErr(sprintf("Error on line %d in %s: %s\n%s", $exception->getLine(), $exception->getFile(), $exception->getMessage(), $exception->getTraceAsString()));
    }

    public static function logStdErr(string $string)
    {
        file_put_contents("php://stderr", $string);
    }
}