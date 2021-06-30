#! /usr/bin/env php
<?php
use com\selfcoders\financetracker\Updater;

require_once __DIR__ . "/../bootstrap.php";

$updater = new Updater;
$updater->run();