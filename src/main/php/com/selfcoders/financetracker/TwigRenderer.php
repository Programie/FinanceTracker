<?php
namespace com\selfcoders\financetracker;

use Symfony\Component\Asset\Package;
use Twig\Environment;
use Twig\Extra\Html\HtmlExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TwigRenderer
{
    private static ?Environment $twig = null;

    public static function init(Package $assetsPackage)
    {
        if (self::$twig !== null) {
            return self::$twig;
        }

        $loader = new FilesystemLoader(VIEWS_ROOT);

        self::$twig = new Environment($loader);
        self::$twig->addExtension(new HtmlExtension);
        self::$twig->addExtension(new IntlExtension);

        self::$twig->addFunction(new TwigFunction("asset", function (string $path) use ($assetsPackage) {
            return $assetsPackage->getUrl($path);
        }));

        return self::$twig;
    }

    public static function render($name, $context = []): string
    {
        return self::$twig->render($name . ".twig", $context);
    }
}