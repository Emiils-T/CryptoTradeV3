<?php
namespace App\Api;

use App\Currency;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class CoinGecko implements CryptoApi
{
    const PARAMS = [
        'vs_currency' => 'usd',
        'order' => 'market_cap_desc',
        'per_page' => 15,
        'page' => 1,
        'sparkline' => 'false'
    ];
    public string $apiKey;


    private const URL = "https://api.coingecko.com";
    private Client $client;

    public function __construct()
    {
        $this->apiKey = $_ENV["COINGECKO_API_KEY"];
        $this->client = new Client([
            'base_uri' => self::URL,
            'timeout' => 2.0,
        ]);
    }

    public function getLatest():array
    {
        $urlSuffix='/api/v3/coins/markets';
        $headers = [
            'Accepts' => 'application/json'
        ];
        $params = http_build_query(self::PARAMS);
        $endpoint =$urlSuffix."?".$params;

        try {
            $response = $this->client->get($endpoint, [
                'headers' => $headers,
            ]);

            $latest = json_decode($response->getBody()->getContents());
            $currencies = [];
            foreach ($latest as $currency) {
                $currencies[]= new Currency(
                    $currency->name,
                    strtoupper($currency->symbol),
                    $currency->current_price
                );
            }
            return $currencies;
        }catch (GuzzleException $exception){
            return [];
        }

    }
}