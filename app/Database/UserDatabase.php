<?php
namespace App\Database;

use Carbon\Carbon;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use App\User;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserDatabase
{
    private string $filePath;

    public function __construct(string $filePath)
    {

        $this->filePath = $filePath;
        $this->connectionParams = array(
            'driver' => 'pdo_sqlite',
            'path' => $this->filePath,
        );
        $this->dbalConnection = DriverManager::getConnection($this->connectionParams);
    }

    public function create()
    {
        $dbalConnection = DriverManager::getConnection($this->connectionParams);

        $schema = new Schema();

        $userTable = $schema->createTable('usersData');
        $userTable->addColumn('id', 'integer');
        $userTable->addColumn('name', 'string');
        $userTable->addColumn('wallet','float');
        $userTable->addColumn('password', 'string');
        $userTable->setPrimaryKey(['id']);

        $platform = $dbalConnection->getDatabasePlatform();
        $sqls= $schema->toSql($platform);

        foreach ($sqls as $sql) {
            $dbalConnection->executeStatement($sql);
        }
        echo "SQLite database created\n";
    }

    public function insert(User $user): void
    {

            $query = "INSERT INTO usersData (name, wallet, password) VALUES (:name, :wallet, :password)";
            $stmt = $this->dbalConnection->prepare($query);
            $stmt->bindValue(':name', $user->getName());
            $stmt->bindValue(':wallet', $user->getWallet());
            $stmt->bindValue(':password', $user->getPassword());
            $stmt->executeQuery();

    }
    public function updateWallet(string $name, float $newWallet): void
    {
        $query = "UPDATE usersData SET wallet = :wallet WHERE name = :name";
        $stmt = $this->dbalConnection->prepare($query);
        $stmt->bindValue(':wallet', $newWallet);
        $stmt->bindValue(':name', $name);
        $stmt->executeQuery();
    }

    public function getAllUsers(): array
    {

            $userData = $this->dbalConnection->fetchAllAssociative("SELECT * FROM usersData");
            $items = [];
            foreach ($userData as $value) {
                $userItem = new User(
                    $value['name'],
                    $value['wallet'],
                    $value['password']
                );
                $items[] = $userItem;
            }
            return $items;

    }
    public function display():void
    {
        $rows = [];
        foreach ($this->getAllUsers() as $user) {
            $rows[] = [
                $user->getName(),
                $user->getWallet(),
                $user->getPassword()
            ];
        }
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Wallet', 'Password']);
        $table->setRows($rows);
        $table->render();
    }


}