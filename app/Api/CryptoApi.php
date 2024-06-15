<?php

namespace App\Api;
use App\Currency;

interface CryptoApi
{
    /**
     * @return array<Currency>
     */
    public function getLatest(): array;

}