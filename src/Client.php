<?php

namespace App\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    protected $client;

    public function __construct(GuzzleClient $client)
    {
        $this->client = $client;
    }

    public function get(string $url, array $options = [])
    {
        return $this->client->get($url, $options);
        //return $this->get($url)->getBody()->getContents();
    }
}