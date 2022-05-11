<?php

declare(strict_types=1);

namespace JeroenG\Explorer\Infrastructure\Elastic;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use JeroenG\Explorer\Application\Results;
use JeroenG\Explorer\Application\SearchCommandInterface;

class Finder
{
    private Client $client;

    private SearchCommandInterface $builder;

    public function __construct(Client $client, SearchCommandInterface $builder)
    {
        $this->client = $client;
        $this->builder = $builder;
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function find(): Results
    {
        $query = [
            'index' => $this->builder->getIndex(),
            'body' => $this->builder->buildQuery(),
        ];

        $rawResults = $this->client->search($query);

        return new Results($rawResults);
    }
}
