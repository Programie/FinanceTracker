<?php
namespace com\selfcoders\financetracker;

use Throwable;

class Utils
{
    public static function printException(Throwable $exception)
    {
        fwrite(STDERR, sprintf("Error on line %d in %s: %s\n%s", $exception->getLine(), $exception->getFile(), $exception->getMessage(), $exception->getTraceAsString()));
    }
}