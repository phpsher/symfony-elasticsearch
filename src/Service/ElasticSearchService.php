<?php

namespace App\Service;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearchService
{
    private Client $client;

    public function __construct(string $elasticsearchHost)
    {
        $this->client = ClientBuilder::create()
            ->setHosts([$elasticsearchHost])
            ->build();
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
