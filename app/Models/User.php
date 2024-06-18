<?php

namespace App\Models;

class User
{
    private string $name;
    private float $wallet;
    private string $baseDir;
    private string $password;

    public function __construct(string $name, float $wallet, string $password)
    {
        $this->name = $name;
        $this->wallet = $wallet;
        $this->password = $password;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWallet(): float
    {
        return $this->wallet;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}