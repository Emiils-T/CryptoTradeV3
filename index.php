<?php

require_once './vendor/autoload.php';

use App\Database\TransactionDatabase;
use App\Database\UserDatabase;
use App\Database\WalletDatabase;
use App\Controllers\CryptoController;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


$baseDir = __DIR__;
$userLocation = __DIR__ . '/Users/UserDatabase.sqlite';
if (!file_exists($userLocation)) {
    $userDatabase = new UserDatabase($userLocation);
    $userDatabase->create();
} else {
    $userDatabase = new UserDatabase($userLocation);
}

$userName = "Emils";
$database = null;
$transactions = null;

$sqliteFile = $baseDir . '/Wallet/' . $userName . '_Wallet.sqlite';
$database = new WalletDatabase($sqliteFile);

$transactionLocation = $baseDir . '/Transactions/' . $userName . '_Transactions.sqlite';
$transactions = new TransactionDatabase($transactionLocation);

$selectedUser = $userDatabase->selectUserByName($userName);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$loader = new FilesystemLoader(__DIR__ . '/Template');
$twig = new Environment($loader, [
    'cache' => false,
]);

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', [CryptoController::class, "showUser"]);
    $r->addRoute('GET', '/currencies', [CryptoController::class, "index"]);
    $r->addRoute('GET', '/search', [CryptoController::class, "searchForm"]);
    $r->addRoute('POST', '/search', [CryptoController::class, "search"]);
    $r->addRoute('GET', '/log', [CryptoController::class, "transactions"]);
    $r->addRoute('GET', '/buy', [CryptoController::class, "buyForm"]);
    $r->addRoute('POST', '/buy', [CryptoController::class, "buy"]);
    $r->addRoute('GET', '/wallets', [CryptoController::class, "showWallet"]);
    $r->addRoute('GET', '/sell', [CryptoController::class, "sellForm"]);
    $r->addRoute('POST', '/sell', [CryptoController::class, "sell"]);

});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        [$controller, $method] = $handler;
        $controllerInstance = new $controller($userDatabase, $database, $transactions, $baseDir);

        if ($method == "sellForm") {
            $data = $controllerInstance->{"showWallet"}(...array_values($vars));
            echo $twig->render('showWallet.html.twig', ['items' => $data]);
        }

        $data = $controllerInstance->{$method}(...array_values($vars));
        echo $twig->render($method . '.html.twig', ['items' => $data]);

        break;
}