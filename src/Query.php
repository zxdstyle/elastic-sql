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

    /**
     * @var array
     */
    public $wheres = [];

    /**
     * @var array
     */
    public $columns = [];

    /**
     * @var array
     */
    public $orders = [];

    /**
     * @var array
     */
    public $aggs = [];

    protected $operators = [
        '='  => 'eq',
        '>'  => 'gt',
        '>=' => 'gte',
        '<'  => 'lt',
        '<=' => 'lte',
        '!=' => 'ne',
    ];

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

    public function where($column, $operator = null, $value = null, string $leaf = 'term', string $boolean = 'and'): self
    {
        // TODO
//        if ($column instanceof Closure) {
//            return $this->whereNested($column, $boolean);
//        }

        if (func_num_args() === 2) {
            list($value, $operator) = [$operator, '='];
        }

        if (is_array($operator)) {
            list($value, $operator) = [$operator, null];
        }

        if (in_array($operator, ['>=', '>', '<=', '<'])) {
            $leaf = 'range';
        }

        if (is_array($value) && $leaf === 'range') {
            $value = [
                $this->operators['>='] => $value[0],
                $this->operators['<='] => $value[1],
            ];
        }

        $type = 'Basic';

        $operator = $operator ? $this->operators[$operator] : $operator;

        $this->wheres[] = compact(
            'type',
            'column',
            'leaf',
            'value',
            'boolean',
            'operator'
        );

        return $this;
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
     * @throws InvalidArgumentException
     */
    public function newQuery(): Query
    {
        $query = new static($this->engine, $this->client);

        return $query->index((string) $this->index)->type((string) $this->type);
    }

    /**
     * @param string $name
     * @param $argument
     * @return Query
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