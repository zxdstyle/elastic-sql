<?php


namespace Zxdstyle\ElasticSql;


use Elasticsearch\Client;
use Zxdstyle\ElasticSql\Exceptions\InvalidArgumentException;

class Query
{
    /** @var Engine */
    protected $engine;

    /** @var Client */
    protected $client;

    protected $index;

    protected $type;

    protected $limit;

    protected $offset;

    public function __construct(Engine $engine, Client $client)
    {
        $this->set('engine', $engine);

        $this->set('client', $client);
    }

    /**
     * @param string $index
     * @return $this
     * @throws InvalidArgumentException
     */
    public function index(string $index): Query
    {
        return $this->set("index", $index);
    }

    /**
     * @param string $type
     * @return $this
     * @throws InvalidArgumentException
     */
    public function type(string $type): Query
    {
        return $this->set("type", $type);
    }

    /**
     * @param int $limit
     * @return $this
     * @throws InvalidArgumentException
     */
    public function limit(int $limit): Query
    {
        return $this->set("limit", $limit);
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return Engine
     */
    public function getEngine(): Engine
    {
        return $this->engine;
    }

    /**
     * @return $this
     */
    public function newQuery(): Query
    {
        return new static($this->engine, $this->client);
    }

    /**
     * @param string $name
     * @param $argument
     * @throws InvalidArgumentException
     */
    protected function set(string $name, $argument)
    {
        if (!property_exists(self::class, $name)) {
            throw new InvalidArgumentException("Passed parameter [$name] is invalid");
        }

        $this->$name = $argument;

        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get(string $name)
    {
        if (!property_exists(self::class, $name)) {
            throw new InvalidArgumentException("Passed parameter [$name] is invalid");

        }

        return $this->$name;
    }
}