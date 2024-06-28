<?php

namespace App\Controllers;

use App\Api\CoinMC;
use App\Api\CryptoApi;
use App\Database\TransactionDatabase;
use App\Database\UserDatabase;
use App\Database\WalletDatabase;
use App\Exchange;
use Exception;

class  CryptoController
{
    private CryptoApi $exchangeApi;
    private UserDatabase $userDatabase;
    private WalletDatabase $walletDatabase;
    private TransactionDatabase $transactionDatabase;
    private string $basedir;

    public function __construct(UserDatabase $userDatabase, WalletDatabase $walletDatabase, TransactionDatabase $transactionDatabase, string $basedir)
    {
        $this->exchangeApi = new CoinMC();
        $this->userDatabase = $userDatabase;
        $this->walletDatabase = $walletDatabase;
        $this->transactionDatabase = $transactionDatabase;
        $this->basedir = $basedir;
    }

    public function index(): array  //currencies
    {
        $user = "Emils";
        $this->exchange = new Exchange($this->basedir, $this->userDatabase->selectUserByName($user), $this->userDatabase, $this->walletDatabase, $this->transactionDatabase);
        $this->exchange->updateWallet();
        return $this->exchangeApi->getLatest();
    }

    public function searchForm() //empty function for router
    {
    }

    /**
     * @throws Exception
     */
    public function search(): array
    {
        $symbol = $_POST['symbol'];
        try {
            $cryptos = $this->exchangeApi->getLatest();
            foreach ($cryptos as $crypto) {
                if ($crypto->getSymbol() === $symbol) {
                    return [$crypto];
                }
            }
        } catch (\Exception $exception) {
            echo "An error occurred: " . $exception->getMessage() . "\n";
        }
        throw new Exception("Error:");
    }

    public function showUser(): array
    {
        $user = $this->userDatabase->selectUserByName("Emils");
        return [$user];
    }

    public function buyForm(): void //empty function for router
    {
    }

    public function buy(): void
    {
        $symbol = $_POST['symbol'];
        $purchasePrice = $_POST['purchaseAmount'];

        $user = "Emils";
        if ($purchasePrice > $this->userDatabase->selectUserByName($user)->getWallet()) {
            throw new \Exception("Insufficient funds.");
        }

        $this->exchange = new Exchange($this->basedir, $this->userDatabase->selectUserByName($user), $this->userDatabase, $this->walletDatabase, $this->transactionDatabase);
        $this->exchange->buy($symbol, $purchasePrice);
    }

    public function sellForm(): void
    {
    }

    public function sell(): void
    {
        $id = (int)$_POST['id'];

        $user = "Emils";

        $this->exchange = new Exchange($this->basedir, $this->userDatabase->selectUserByName($user), $this->userDatabase, $this->walletDatabase, $this->transactionDatabase);
        $this->exchange->sell($id);

    }

    public function transactions(): array
    {
        return $this->transactionDatabase->getAll();
    }

    public function showWallet(): array
    {
        return $this->walletDatabase->getAll();
    }

}