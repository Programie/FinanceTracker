<?php
use com\selfcoders\financetracker\Database;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once __DIR__ . "/bootstrap.php";

return ConsoleRunner::createHelperSet(Database::getEntityManager());
