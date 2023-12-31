<?php
use com\selfcoders\financetracker\Controller;

require_once __DIR__ . "/../bootstrap.php";

$router = new AltoRouter;

$router->map(method: "GET", route: "/", target: "redirectToDefaultWatchlist");
$router->map(method: "GET", route: "/watchlist/[:name]", target: "getContent");
$router->map(method: "GET", route: "/watchlist/[:name].json", target: "getJson");
$router->map(method: "POST", route: "/watchlist/[:name]/notifications", target: "toggleNotifications");
$router->map(method: "POST", route: "/watchlist/[:name]", target: "createEntry");
$router->map(method: "POST", route: "/watchlist/[:name]/[:id]", target: "updateEntry");
$router->map(method: "POST", route: "/watchlist/[:name]/[:id]/reset-notified", target: "resetNotified");
$router->map(method: "DELETE", route: "/watchlist/[:name]/[:id]", target: "removeEntry");
$router->map(method: "GET", route: "/isin/[:isin]/current-price", target: "getCurrentPrice");
$router->map(method: "GET", route: "/isin/[:isin]/original-name", target: "getOriginalName");
$router->map(method: "GET", route: "/news.json", target: "getNews");
$router->map(method: "GET", route: "/news/[:isin].json", target: "getNewsForEntry");
$router->map(method: "GET", route: "/news/[:isin].html", target: "getNewsHtmlForEntry");
$router->map(method: "GET", route: "/grafana", target: "emptyResponse");
$router->map(method: "POST", route: "/grafana/search", target: "grafanaSearch");
$router->map(method: "POST", route: "/grafana/query", target: "grafanaQuery");
$router->map(method: "GET", route: "/coinmarketcap/[:symbol]", target: "redirectToCoinMarketCap");

$match = $router->match();

if ($match === false) {
    http_response_code(404);
} else {
    $target = $match["target"];
    $params = array_map("trim", $match["params"]);

    $controller = new Controller;

    $controller->{$target}($params);
}