<?php
namespace App;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

class Database
{
    private array $connectionParams;
    private $filePath;
    private $dbalConnection;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;

        $this->connectionParams = array(
            'driver' => 'pdo_sqlite',
            'path' => $this->filePath,
        );
        $this->dbalConnection = DriverManager::getConnection($this->connectionParams);
    }

    public function createDatabase():void
    {
        $dbalConnection = DriverManager::getConnection($this->connectionParams);

        $schema = new Schema();

        $usersTable = $schema->createTable('cryptoWallet');
        $usersTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $usersTable->addColumn('name', 'string', ['length' => 50]);
        $usersTable->addColumn('symbol', 'string', ['length' => 20]);
        $usersTable->addColumn('amount', 'float');
        $usersTable->addColumn('price', 'float');
        $usersTable->addColumn('purchasePrice', 'float');
        $usersTable->addColumn('dateOfPurchase', 'string');
        $usersTable->addColumn('value', 'float');
        $usersTable->addColumn('valueNow', 'float');
        $usersTable->addColumn('profit', 'float');
        $usersTable->setPrimaryKey(['id']);

        $platform = $dbalConnection->getDatabasePlatform();
        $sqls = $schema->toSql($platform);

        foreach ($sqls as $sql) {
            $dbalConnection->executeStatement($sql);
        }

        echo "SQLite database created successfully at: {$this->filePath}\n";
    }
    public function insertWallet(Wallet $wallet): int
    {
        $query = "INSERT INTO cryptoWallet (name, symbol, amount, price, purchasePrice, dateOfPurchase, value, valueNow, profit)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->dbalConnection->prepare($query);
        $stmt->bindValue(1, $wallet->getName());
        $stmt->bindValue(2, $wallet->getSymbol());
        $stmt->bindValue(3, $wallet->getAmount());
        $stmt->bindValue(4, $wallet->getPrice());
        $stmt->bindValue(5, $wallet->getPurchasePrice());
        $stmt->bindValue(6, $wallet->getDateOfPurchase()->toDateTimeString());
        $stmt->bindValue(7, $wallet->getValue());
        $stmt->bindValue(8, $wallet->getValueNow());
        $stmt->bindValue(9, $wallet->getProfit());
        $stmt->executeQuery();

        return (int)$this->dbalConnection->lastInsertId();
//
    }

    public function getAll():array
    {
        return $this->dbalConnection->fetchAllAssociative('SELECT * FROM cryptoWallet');
    }

    public function deleteWallet(int $id): void
    {
        $this->dbalConnection->delete('cryptoWallet', ['id' => $id]);
    }


    public function updateWallet(int $id, array $data): void
    {
        $this->dbalConnection->update('cryptoWallet', $data, ['id' => $id]);
    }

    public function insert(array $data):void
    {
        $this->dbalConnection->insert("cryptoWallet",$data);
    }
}