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
        $this->latestUpdate= $this->exchangeApi->getLatest();
        $this->wallet=$this->getWallet();

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
        $this->wallet[]= $coin;
    }

    public function sell():void//works
    {
        $id = (int)readline("Enter the database ID to select crypto: ");
        $cryptoToSell = null;

        foreach ($this->wallet as  $crypto) {
            if ($crypto->getId() === $id) {
                $cryptoToSell = $crypto;
                break;
            }
        }

        if ($cryptoToSell) {
            $valueNow = $cryptoToSell->getValueNow();
            $amount = $cryptoToSell->getAmount();
            $symbol= $cryptoToSell->getSymbol();

            $selectedUser = $this->userDatabase->selectUserByName($this->user->getName());

            $data = ($selectedUser->getWallet()+$valueNow);
            $this->userDatabase->updateUserWalletByName($this->user->getName(), $data);


            $this->database->deleteWallet($id);

            $transaction = new Transaction(
                $this->user->getName(),
                'sell',
                $symbol,
                $amount,
                Carbon::now('Europe/Riga'),
            );
            $this->transactionDatabase->insert($transaction);

        } else {
            echo "Invalid ID provided.\n";
        }
    }

    public function buy():void//works
    {
        $index = (int) readline("Enter index to select Crypto: ");
        $selectedCrypto = $this->selectCrypto($index);
        $name = $selectedCrypto->getName();
        $symbol = $selectedCrypto->getSymbol();
        $price = $selectedCrypto->getPrice();
        $purchasePrice = (int) readline("Enter how much to buy in USD: ");
        $amount = $purchasePrice / $price; // Dollars worth
        $dateOfPurchase = Carbon::now('Europe/Riga');
        $value = $amount * $price;
        $valueNow = $amount * $price;

        $selectedUser = $this->userDatabase->selectUserByName($this->user->getName());

        $data = ($selectedUser->getWallet()-$valueNow);
        $this->userDatabase->updateUserWalletByName($this->user->getName(), $data);


        $crypto = new Wallet($name, $symbol, $amount, $price, $purchasePrice, $dateOfPurchase, $value, $valueNow);
        $this->addToWallet($crypto);


        $transaction = new Transaction(
            $this->user->getName(),
            'buy',
            $symbol,
            $amount,
            $dateOfPurchase
        );
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

    public function searchAndDisplay(string $symbol):void//works
    {
        $selectedCrypto = $this->search($symbol);
        foreach ($this->crypto as $key => $crypto) {

            if ($crypto->getSymbol() === $symbol) {
                $selectedCrypto = $this->crypto[$key];
            }
        }
        $rows = [];
        $rows[] = [
            $selectedCrypto->getName(),
            $selectedCrypto->getSymbol(),
            $selectedCrypto->getPrice()
        ];

        $output = new ConsoleOutput();
        $table = new Table($output);
        $table
            ->setHeaders([
                "Name",
                "Symbol",
                "Price"
            ])
            ->setRows($rows);
        $table->render();
    }

    public function search(string $symbol): ?Currency
    {
        foreach ($this->crypto as $crypto) {
            if ($crypto->getSymbol() === $symbol) {
                return $crypto;
            }
        }

        echo "Error: couldn't find crypto $symbol\n";
        return null;
    }

    public function displayCrypto(): void
    {
        $rows = [];

        foreach ($this->crypto as $index => $crypto) {
            $rows[] = [$index, $crypto->getName(), $crypto->getSymbol(), $crypto->getPrice()];
        }

        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders(["Index", "Name", "Symbol", "Price"])->setRows($rows);
        $table->render();
    }

    public function displayWallet(): void
    {
        $this->updateWallet();
        $wallet = $this->getWallet();
        $rows = [];
        foreach ($wallet as $crypto) {
            $rows[] = [
                $crypto->getId(),
                $crypto->getName(),
                $crypto->getSymbol(),
                $crypto->getPrice(),
                $crypto->getPurchasePrice(),
                number_format($crypto->getAmount(),5),
                $crypto->getDateOfPurchase(),
                $crypto->getValue(),
                number_format($crypto->getValueNow(),5),
                number_format($crypto->getProfit(),5)
            ];
        }
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders([
            "ID",
            "Name",
            "Symbol",
            "Price",
            "Purchase Price",
            "Amount",
            "DateOfPurchase",
            "Value",
            "Value Now",
            "Profit/Loss"]);
        $table->setRows($rows);
        $table->render();
    }
}