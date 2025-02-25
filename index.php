<?php

require_once './vendor/autoload.php';

use App\Database\UserDatabase;
use App\Exchange;
use App\Database\Activity;
use App\Database\Log;
use App\Database\TransactionLog;
use App\User;
use Carbon\Carbon;
use App\Database;
use App\Database\TransactionDatabase;
use App\Database\Transaction;



$sqliteFile = __DIR__ . '/Wallet/crypto_wallet.sqlite';
$database = new Database($sqliteFile);
/*$database->createDatabase();*/
//
$transactionLocation = __DIR__ . '/Transactions/Transactions.sqlite';
$transactions = new TransactionDatabase($transactionLocation);
/*$transactions->create();*/

$userLocation = __DIR__ . '/UserTest/UserTest.sqlite';
$userDatabase = new UserDatabase($transactionLocation);
$userDatabase->create();


$username ="emils";
$password = "asdkjhasd";
$wallet=1000;
$newUser = new User($username,$wallet,$password);
$userDatabase->insert($newUser);


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$baseDir = __DIR__;
$userFilePath = __DIR__ . "/Users/UserWallet.json";
$user = User::loadFromFile($baseDir);

if ($user === null) {
    $name = (string)readline("Enter user name: ");
    $walletAmount = (float)readline("Enter wallet amount: ");
    $user = new User($name, $walletAmount, $baseDir);
    $user->saveToFile();
}

while (true) {
    $user->displayUser();
    echo "1. List top crypto\n2. Search for crypto by Symbol\n3. Buy crypto\n4. Sell crypto\n";
    echo "5. Display Wallet\n6. Transaction List\n7. Exit\n";
    $choice = (int)readline("Enter index to select choice: ");

    switch ($choice) {
        case 1:
            $exchange = new Exchange($baseDir, $user, $database,$transactions);
            $exchange->displayCrypto();
            break;
        case 2:
            $symbol = strtoupper((string)readline("Enter symbol: "));
            $exchange = new Exchange($baseDir, $user,$database,$transactions);
            $exchange->searchAndDisplay($symbol);

            $activity = new Activity("{$user->getName()}:Searched for $symbol", carbon::now());
            $log = new Log($baseDir);
            $log->addActivityToLog($activity);
            break;
        case 3:
            $exchange = new Exchange($baseDir, $user,$database,$transactions);
            $exchange->displayCrypto();
            $exchange->buy();
            break;
        case 4:
            $exchange = new Exchange($baseDir, $user, $database,$transactions);
            $exchange->displayWallet();
            $exchange->sell();
            break;
        case 5:
            $exchange = new Exchange($baseDir, $user, $database,$transactions);
            $exchange->displayWallet();
            $activity = new Activity("{$user->getName()}:Looked at wallet", carbon::now());
            $log = new Log($baseDir);
            $log->addActivityToLog($activity);
            break;
        case 6:
            $transactions->display();
            break;
        case 7:
            $user->saveToFile();
            exit;
        default:
            echo "Error: Wrong Input";
            break;
    }
}