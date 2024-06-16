<?php

require_once './vendor/autoload.php';

use App\Database\TransactionDatabase;
use App\Database\UserDatabase;
use App\Database\WalletDatabase;
use App\Exchange;
use App\Models\User;
use App\Ask;


$baseDir = __DIR__;
$userLocation = __DIR__ . '/Users/UserDatabase.sqlite';
if(!file_exists($userLocation)){
    $userDatabase = new UserDatabase($userLocation);
    $userDatabase->create();
}else{
    $userDatabase = new UserDatabase($userLocation);
}

$selectedUser=null;
$database=null;
$transactions=null;
echo "\n1. Create new User\n2. Log into existing User\n";
$choice = (int) readline("Enter choice: ");

switch($choice){
    case 1:
        $selectedUser = Ask::createUser($userDatabase,$baseDir);
        [$database,$transactions]=Ask::setupWalletAndTransactions($selectedUser->getName(),$baseDir);
        break;
    case 2:
        $selectedUser = Ask::login($userDatabase);
        [$database,$transactions]=Ask::setupWalletAndTransactions($selectedUser->getName(),$baseDir);
        break;
    default:
        echo "Invalid input\n";
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


while (true) {
    $userDatabase->displayUser($selectedUser->getName());
    echo "1. List top crypto\n2. Search for crypto by Symbol\n3. Buy crypto\n4. Sell crypto\n";
    echo "5. Display Wallet\n6. Transaction List\n7. Exit\n";
    $choice = (int)readline("Enter index to select choice: ");

    switch ($choice) {
        case 1:
            $exchange = new Exchange($baseDir, $selectedUser,$userDatabase, $database,$transactions);
            $exchange->displayCrypto();
            break;
        case 2:
            $symbol = strtoupper((string)readline("Enter symbol: "));
            $exchange = new Exchange($baseDir, $selectedUser,$userDatabase,$database,$transactions);
            $exchange->searchAndDisplay($symbol);

            break;
        case 3:
            $exchange = new Exchange($baseDir, $selectedUser,$userDatabase,$database,$transactions);
            $exchange->displayCrypto();
            $exchange->buy();
            break;
        case 4:
            $exchange = new Exchange($baseDir, $selectedUser,$userDatabase, $database,$transactions);
            $exchange->displayWallet();
            $exchange->sell();
            break;
        case 5:
            $exchange = new Exchange($baseDir, $selectedUser,$userDatabase, $database,$transactions);
            $exchange->displayWallet();

            break;
        case 6:
            $transactions->display();
            break;
        case 7:
            exit;
        default:
            echo "Error: Wrong Input";
            break;
    }
}