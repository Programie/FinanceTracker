<?php
use com\selfcoders\financetracker\Controller;

require_once __DIR__ . "/../bootstrap.php";

$router = new AltoRouter;

$router->map(method: "GET", route: "/", target: "redirectToDefaultWatchlist");
$router->map(method: "GET", route: "/[:name]", target: "getContent");
$router->map(method: "GET", route: "/[:name].json", target: "getJson");
$router->map(method: "GET", route: "/[:name]/[:isin]/current-price", target: "getCurrentPrice");
$router->map(method: "POST", route: "/[:name]/[:isin]", target: "updateEntry");
$router->map(method: "POST", route: "/[:name]/[:isin]/reset-notified", target: "resetNotified");
$router->map(method: "DELETE", route: "/[:name]/[:isin]", target: "removeEntry");

$match = $router->match();

if ($match === false) {
    http_response_code(404);
} else {
    $target = $match["target"];

    $controller = new Controller;

    $controller->{$target}($match["params"]);
}