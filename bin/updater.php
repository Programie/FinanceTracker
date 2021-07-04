#! /usr/bin/env php
<?php
use com\selfcoders\financetracker\updater\Updater;

require_once __DIR__ . "/../bootstrap.php";

$updater = new Updater;
$updater->run();