<?php
namespace com\selfcoders\financetracker\orm;

use com\selfcoders\financetracker\Date;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

class DateType extends DateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Date) {
            return $value->toUtc()->format("Y-m-d H:i:s");
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ["null", "Date"]);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        return Date::fromUtc($value);
    }
}