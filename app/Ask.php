<?php

namespace App;

use App\Database\TransactionDatabase;
use App\Database\UserDatabase;
use App\Database\WalletDatabase;
use App\Models\User;


class Ask
{
    public static function createUser(UserDatabase $userDatabase, string $baseDir): User
    {
        $username = (string)readline("Enter user name:");
        $password = md5(readline("Enter password:"));
        $wallet = (float)readline("Enter wallet amount:");

        if ($wallet <= 0) {
            echo "Wallet value cannot be less than 1\n";
            exit;
        }
        $user = new User($username, $wallet, $password);
        $userDatabase->insert($user);

        $sqliteFile = $baseDir . '/Wallet/' . $username . '_Wallet.sqlite';

        $walletDatabase = new WalletDatabase($sqliteFile);
        $walletDatabase->createDatabase();


        $transactionLocation = $baseDir . '/Transactions/' . $username . '_Transactions.sqlite';
        $transactions = new TransactionDatabase($transactionLocation);
        $transactions->create();

        return $userDatabase->selectUserByName($username);
    }

    public static function login(UserDatabase $userDatabase): User
    {
        $userDatabase->displayAll();
        $userName = (string)readline("Enter user name:");
        $userData = $userDatabase->selectUserByName($userName);
        $password = md5(readline("Enter password:"));

        if ($userData->getPassword() === $password) {
            {
                echo "Access granted\n";
                return $userData;
            }
        } else {
            echo "Wrong password\n";
            exit;
        }


    }

    public static function setupWalletAndTransactions(string $username, string $baseDir): array
    {
        $sqliteFile = $baseDir . '/Wallet/' . $username . '_Wallet.sqlite';
        $database = new WalletDatabase($sqliteFile);

        $transactionLocation = $baseDir . '/Transactions/' . $username . '_Transactions.sqlite';
        $transactions = new TransactionDatabase($transactionLocation);

        return [$database, $transactions];
    }
}