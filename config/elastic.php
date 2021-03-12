<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Elasticsearch Client Configuration
    |--------------------------------------------------------------------------
    |
    | This array will be passed to the Elasticsearch client.
    | See configuration options here:
    |
    | https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/configuration.html
    */
    'config' => [
        // Set Elasticsearch host address
        'hosts' => [
            env("ELASTIC_HOST", "http://localhost:9200"),
            // Multi-host can be added here
        ],

        // Set the number of retries
        'retries' => env("ELASTIC_RETRIES", 1),

        // Set up Elasticsearch connection pool
        'connectionPool' => [Elasticsearch\ConnectionPool\StaticNoPingConnectionPool::class, []],

        // Set up selector
        'selector' => Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector::class,

        // Other Elasticsearch configuration items can also be added here
    ],

    'index' => env("ELASTIC_DEFAULT_INDEX", "index")
];