<?php
namespace com\selfcoders\financetracker;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigRenderer
{
    private static ?Environment $twig = null;

    public static function getInstance(): Environment
    {
        if (self::$twig !== null) {
            return self::$twig;
        }

        $loader = new FilesystemLoader(VIEWS_ROOT);

        self::$twig = new Environment($loader);

        return self::$twig;
    }

    public static function render($name, $context = []): string
    {
        return self::getInstance()->render($name . ".twig", $context);
    }
}