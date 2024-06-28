<?php

namespace App;

use App\Api\CoinMC;
use App\Api\CryptoApi;
use App\Database\Transaction;
use App\Database\TransactionDatabase;
use App\Database\UserDatabase;
use App\Database\WalletDatabase;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;


class Exchange
{
    private array $crypto;
    private string $baseDir;
    private array $wallet;
    private User $user;
    private CryptoApi $exchangeApi;
    private WalletDatabase $database;
    private TransactionDatabase $transactionDatabase;
    private UserDatabase $userDatabase;

    private array $latestUpdate;


    public function __construct(string $baseDir, User $user, UserDatabase $userDatabase, WalletDatabase $database, TransactionDatabase $transactionDatabase)
    {
        $this->database = $database;
        $this->crypto = $this->getCryptoList();
        $this->baseDir = $baseDir;
        $this->user = $user;
        $this->latestUpdate = $this->exchangeApi->getLatest();
        $this->wallet = $this->getWallet();

        $this->transactionDatabase = $transactionDatabase;
        $this->userDatabase = $userDatabase;
    }


    public function getCryptoList(): array
    {
        $this->exchangeApi = new CoinMC();//can be CoinGecko or CoinMC
        return $this->exchangeApi->getLatest();
    }


    public function selectCrypto(int $index)
    {
        $cryptoList = $this->getCryptoList();

        return $cryptoList[$index];
    }

    public function getWallet(): array
    {
        $walletData = $this->database->getAll();
        $items = [];
        foreach ($walletData as $value) {
            $walletItem = new Wallet(
                $value['name'],
                $value['symbol'],
                $value['amount'],
                $value['price'],
                $value['purchasePrice'],
                Carbon::parse($value['dateOfPurchase']),
                $value['value'],
                $value['valueNow'],
                $value['profit']
            );
            $walletItem->setId($value['id']);
            $items[] = $walletItem;
        }
        return $items;
    }


    public function addToWallet(Wallet $coin): void
    {
        $id = $this->database->insertWallet($coin);
        $coin->setId($id);
        $this->wallet[] = $coin;
    }

    public function sell(int $id): void
    {
        $cryptoToSell = null;

        foreach ($this->wallet as $crypto) {
            if ($crypto->getId() === $id) {
                $cryptoToSell = $crypto;
                break;
            }
        }

        if ($cryptoToSell) {
            $valueNow = $cryptoToSell->getValueNow();
            $amount = $cryptoToSell->getAmount();
            $symbol = $cryptoToSell->getSymbol();

            $selectedUser = $this->userDatabase->selectUserByName($this->user->getName());
            $data = ($selectedUser->getWallet() + $valueNow);
            $this->userDatabase->updateUserWalletByName($this->user->getName(), $data);

            $this->database->deleteWallet($id);

            $transaction = new Transaction($this->user->getName(), 'sell', $symbol, $amount, Carbon::now('Europe/Riga'));
            $this->transactionDatabase->insert($transaction);
        } else {
            throw new \Exception("Invalid ID provided.\n");
        }
    }

    public function buy(string $symbol, int $purchasePrice): void
    {
        $selectedCrypto = $this->search($symbol);
        $name = $selectedCrypto->getName();
        $symbol = $selectedCrypto->getSymbol();
        $price = $selectedCrypto->getPrice();
        $amount = $purchasePrice / $price;
        $dateOfPurchase = Carbon::now('Europe/Riga');
        $value = $amount * $price;
        $valueNow = $amount * $price;

        $selectedUser = $this->userDatabase->selectUserByName($this->user->getName());
        $data = ($selectedUser->getWallet() - $valueNow);
        $this->userDatabase->updateUserWalletByName($this->user->getName(), $data);

        $crypto = new Wallet($name, $symbol, $amount, $price, $purchasePrice, $dateOfPurchase, $value, $valueNow);
        $this->addToWallet($crypto);

        $transaction = new Transaction($this->user->getName(), 'buy', $symbol, $amount, $dateOfPurchase);
        $this->transactionDatabase->insert($transaction);
    }

    public function updateWallet(): void//works
    {
        foreach ($this->wallet as $crypto) {
            $search = $this->search($crypto->getSymbol());
            if ($search) {
                $valueNow = $search->getPrice() * $crypto->getAmount();
                $crypto->setValueNow($valueNow);
                $crypto->setProfit($crypto->getValueNow() - $crypto->getValue());

                $data = [
                    'valueNow' => $crypto->getValueNow(),
                    'profit' => $crypto->getProfit()
                ];
                $this->database->updateWallet($crypto->getId(), $data);
            }
        }
    }


    public function search(string $symbol): ?Currency
    {
        try {
            foreach ($this->crypto as $crypto) {
                if ($crypto->getSymbol() === $symbol) {
                    return $crypto;
                }
            }
        } catch (\Exception $exception) {
            echo "An error occurred: " . $exception->getMessage() . "\n";
        }
        throw new \Exception("Could not find $symbol.");

    }
}