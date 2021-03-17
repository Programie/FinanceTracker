<?php
namespace com\selfcoders\financetracker\orm;

use com\selfcoders\financetracker\Date;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

class DateType extends DateTimeType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null or $value instanceof Date) {
            return $value;
        }

        return new Date($value);
    }
}