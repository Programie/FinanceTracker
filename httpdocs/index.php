<?php
use com\selfcoders\financetracker\Controller;

require_once __DIR__ . "/../bootstrap.php";

$router = new AltoRouter;

$router->map(method: "GET", route: "/", target: "redirectToDefaultWatchlist");
$router->map(method: "GET", route: "/watchlist/[:name]", target: "getContent");
$router->map(method: "GET", route: "/watchlist/[:name].json", target: "getJson");
$router->map(method: "POST", route: "/watchlist/[:name]/[:isin]", target: "updateEntry");
$router->map(method: "POST", route: "/watchlist/[:name]/[:isin]/reset-notified", target: "resetNotified");
$router->map(method: "DELETE", route: "/watchlist/[:name]/[:isin]", target: "removeEntry");
$router->map(method: "GET", route: "/isin/[:isin]/current-price", target: "getCurrentPrice");
$router->map(method:"GET", route: "/news.json", target: "getNews");
$router->map(method:"GET", route: "/news/[:isin].json", target: "getNewsForEntry");
$router->map(method:"GET", route: "/news/[:isin].html", target: "getNewsHtmlForEntry");

$match = $router->match();

if ($match === false) {
    http_response_code(404);
} else {
    $target = $match["target"];

    $controller = new Controller;

    $controller->{$target}($match["params"]);
}