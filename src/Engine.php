<?php


namespace Zxdstyle\ElasticSql;


use Illuminate\Support\Arr;
use Zxdstyle\ElasticSql\Exceptions\ElasticException;

class Engine
{
    protected $attributes = [
        '_source' => 'columns',
        'query'   => 'wheres',
        'aggs',
        'sort'   => 'orders',
        'size'   => 'limit',
        'from'   => 'offset',
        'index'  => 'index',
        'type'   => 'type',
        'scroll' => 'scroll',
    ];

    public function resolveIndex(Query $query)
    {
        return $query->get("index");
    }

    public function resolveType(Query $query)
    {
        return $query->get("type");
    }

    public function resolveCreate(Query $query, $id, array $data) :array
    {
        return array_merge([
            'id' => $id,
            'body' => $data
        ], $this->resolve($query));
    }

    public function resolveDelete(Query $query, $id): array
    {
        return array_merge([
            'id' => $id,
        ], $this->resolve($query));
    }

    public function resolveSelect(Query $query): array
    {
        $body = $this->resolve($query);

        $params = [ 'index' => $body['index'], 'type' => $body['type']];

        unset($body['index']);unset($body['type']);

        $params['body'] = $body;

        return $params;
    }

    public function resolveUpdate(Query $query, $id, array $data): array
    {
        return array_merge([
            'id'   => $id,
            'body' => ['doc' => $data],
        ], $this->resolve($query));
    }

    protected function resolve(Query $query) :array
    {
        $body = [];
            foreach ($this->attributes as $key => $attribute) {
                $method = 'resolve'.ucfirst($attribute);
                try {
                    $val = $query->get($attribute);
                    if (empty($val)) {
                        continue;
                    }

                    $body[is_numeric($key) ? $attribute : $key] = $this->$method($query, $val);
                } catch (ElasticException $exception) {
                    continue;
                }
            }


        return $body;
    }
}