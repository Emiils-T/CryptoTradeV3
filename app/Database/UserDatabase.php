<?php

namespace App\Database;

use App\Models\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
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
    public function selectUserByName(string $name):?User
    {
        $query = "SELECT * FROM usersData WHERE name = :name";
        $stmt = $this->dbalConnection->prepare($query);
        $stmt->bindValue(':name', $name);
        $result = $stmt->executeQuery()->fetchAssociative();

        if($result){
            return new User(
                $result['name'],
                $result['wallet'],
                $result['password']
            );
        }
        return null;
    }

    public function updateUserWalletByName(string $name,int $value):void
    {
        $this->dbalConnection->update('usersData', ['wallet'=>$value] ,['name' => $name]);
    }
    public function displayAll():void
    {
        $this->checkEmpty();
        $rows = [];
        foreach ($this->getAllUsers() as $user) {
            $rows[] = [
                $user->getName(),
            ];
        }
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders(['Name']);
        $table->setRows($rows);
        $table->render();
    }
    public function checkEmpty():void
    {
        $check = $this->getAllUsers();
        if(!$check){
            echo "ERROR: User database empty\n";
            exit;
        }
    }

    public function displayUser(string $name):void
    {
        $user=$this->selectUserByName($name);
        $rows = [];
        $rows[]=[
            $user->getName(),
            $user->getWallet(),
        ];
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Wallet']);
        $table->setRows($rows);
        $table->render();
    }
}