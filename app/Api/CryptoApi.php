<?php

namespace App\Api;
use App\Models\Currency;

interface CryptoApi
{
    /**
     * @return array<\App\Models\Currency>
     */
    public function getLatest(): array;

}