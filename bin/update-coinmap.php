#! /usr/bin/env php
<?php
use com\selfcoders\financetracker\IconHelper;

require_once __DIR__ . "/../bootstrap.php";

IconHelper::updateCoinMap($argv[1]);