<?php
use com\selfcoders\financetracker\TwigRenderer;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

require_once __DIR__ . "/vendor/autoload.php";

define("SRC_ROOT", __DIR__ . "/src/main/php");
define("VIEWS_ROOT", __DIR__ . "/src/main/resources/views");

$assetsPackage = new Package(new JsonManifestVersionStrategy(__DIR__ . "/webpack.assets.json"));

TwigRenderer::init($assetsPackage);