<?php

namespace App\Database;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Carbon\Carbon;
use App\Database\Transaction;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;


class TransactionDatabase
{

    private $filePath;
    private $dbalConnection;
    private array $connectionParams;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        $this->connectionParams = array(
            'driver' => 'pdo_sqlite',
            'path' => $this->filePath,
        );
        $this->dbalConnection = DriverManager::getConnection($this->connectionParams);
    }

    public function create():void
    {
        $dbalConnection = DriverManager::getConnection($this->connectionParams);

        $schema = new Schema();

        $transactionTable = $schema->createTable('transactions');
        $transactionTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $transactionTable->addColumn('user', 'string', ['length' => 50]);
        $transactionTable->addColumn('type', 'string', ['length' => 20]);
        $transactionTable->addColumn('symbol', 'string');
        $transactionTable->addColumn('amount', 'float');
        $transactionTable->addColumn('date', 'string');
        $transactionTable->setPrimaryKey(['id']);

        $platform = $dbalConnection->getDatabasePlatform();
        $sqls = $schema->toSql($platform);

        foreach ($sqls as $sql) {
            $dbalConnection->executeStatement($sql);
        }
    }

    public function insert(Transaction $transaction): void
    {

        $query = "INSERT INTO transactions (user , type, symbol, amount, date)
              VALUES (:user, :type, :symbol, :amount, :date)";
        $stmt = $this->dbalConnection->prepare($query);
        $stmt->bindValue(':user', $transaction->getUser());
        $stmt->bindValue(':type', $transaction->getType());
        $stmt->bindValue(':symbol', $transaction->getSymbol());
        $stmt->bindValue(':amount', $transaction->getAmount());
        $stmt->bindValue(':date', $transaction->getDate());
        $stmt->executeQuery();

    }
    public function getAll(): array
    {

            $transactionData = $this->dbalConnection->fetchAllAssociative("SELECT * FROM transactions");
            $items = [];
            foreach ($transactionData as $transaction) {
                $transactionItem = new Transaction(
                    $transaction['user'],
                    $transaction['type'],
                    $transaction['symbol'],
                    $transaction['amount'],
                    Carbon::parse($transaction['date'])
                );
                $items[] = $transactionItem;
            }
            return $items;

    }

    public function display():void
    {
        $rows = [];
        foreach ($this->getAll() as $transaction) {
            $rows[] = [
                $transaction->getUser(),
                $transaction->getType(),
                $transaction->getSymbol(),
                $transaction->getAmount(),
                $transaction->getDate()
            ];
        }
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders([
            'Users',
            'Type',
            'Symbol',
            'Amount',
            'Date',
        ]);
        $table->setRows($rows);
        $table->render();
    }


}