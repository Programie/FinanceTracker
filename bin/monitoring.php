#! /usr/bin/env php
<?php
use com\selfcoders\financetracker\Monitoring;

require_once __DIR__ . "/../bootstrap.php";

$monitoring = new Monitoring;
$monitoring->checkAllEntries();