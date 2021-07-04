<?php
namespace com\selfcoders\financetracker\updater;

class UpdateEntry
{
    public const TYPE_FAST = "fast";
    public const TYPE_NORMAL = "normal";

    public string $updateType;
    public string $isin;
    public ?string $wkn;
    public bool $hasState;

    public function __construct(string $updateType, string $isin, ?string $wkn, bool $hasState)
    {
        $this->updateType = $updateType;
        $this->isin = $isin;
        $this->wkn = $wkn;
        $this->hasState = $hasState;
    }
}