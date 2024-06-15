<?php

namespace App\Api;

use App\Currency;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

Class CoinMC implements CryptoApi
{
    const PARAMS = ["limit" =>  "15"];
    public string $apiKey;


    private const URL = "https://pro-api.coinmarketcap.com/";
    private Client $client;

    public function __construct()
    {
        $this->apiKey = $_ENV["COINMARKET_API_KEY"];
        $this->client = new Client([
            'base_uri' => self::URL,
            'timeout' => 2.0,
        ]);
    }


    public function getLatest():array
    {
        $urlSuffix='v1/cryptocurrency/listings/latest';
        $headers = [
            'Accepts' => 'application/json',
            "X-CMC_PRO_API_KEY" => $this->apiKey
        ];
        $params = http_build_query(self::PARAMS);
        $endpoint =$urlSuffix."?".$params;

        try {
            $response = $this->client->get($endpoint, [
            'headers' => $headers,
            ]);

            $latest = json_decode($response->getBody()->getContents());

            $currencies = [];
            foreach ($latest->data as $currency) {
                $currencies[]= new Currency(
                    $currency->name,
                    $currency->symbol,
                    $currency->quote->USD->price
                );
            }
            return $currencies;
        }catch (GuzzleException $exception){
            var_dump($exception);
            return [];
        }

    }
}
