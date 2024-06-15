<?php


namespace App\Database;

use Carbon\Carbon;
use JsonSerializable;

class Transaction implements JsonSerializable
{
    private Carbon $date;
    private string $user;
    private string $type;
    private float $amount;
    private string $symbol;


    public function __construct
    (
        string $user,
        string $type,
        string $symbol,
        float $amount,
        Carbon $date)
    {
        $this->user = $user;
        $this->type = $type;
        $this->symbol = $symbol;
        $this->amount = $amount;
        $this->date = $date;

    }


    public function getUser(): string
    {
        return $this->user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    function jsonSerialize(): array
    {
        return [

            'date' => $this->date,
        ];
    }
}