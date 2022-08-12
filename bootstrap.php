<?php
use com\selfcoders\financetracker\TwigRenderer;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

require_once __DIR__ . "/vendor/autoload.php";

const SRC_ROOT = __DIR__ . "/src/main/php";
const RESOURCES_ROOT = __DIR__ . "/src/main/resources";
const VIEWS_ROOT = RESOURCES_ROOT . "/views";

$assetsPackage = new Package(new JsonManifestVersionStrategy(__DIR__ . "/webpack.assets.json"));

TwigRenderer::init($assetsPackage);