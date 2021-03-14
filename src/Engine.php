<?php


namespace Zxdstyle\ElasticSql;

use Zxdstyle\ElasticSql\Exceptions\ElasticException;
use Zxdstyle\ElasticSql\Exceptions\InvalidIndexException;

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

    public function resolveLimit(Query $query): int
    {
        return $query->get('limit');
    }

    /**
     * @param Query $builder
     *
     * @return array
     */
    public function resolveAggs(Query $builder): array
    {
        $aggs = [];

        foreach ($builder->aggs as $field => $aggItem) {
            if (is_array($aggItem)) {
                $aggs[] = $aggItem;
            } else {
                $aggs[$field.'_'.$aggItem] = [$aggItem => ['field' => $field]];
            }
        }

        return $aggs;
    }

    /**
     * @param Query $builder
     *
     * @return array
     */
    public function resolveColumns(Query $builder): array
    {
        return $builder->columns;
    }

    public function resolveOrders(Query $builder): array
    {
        $orders = [];

        foreach ($builder->orders as $field => $orderItem) {
            $orders[$field] = is_array($orderItem) ? $orderItem : ['order' => $orderItem];
        }

        return $orders;
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

    public function resolveFlush(Query $query): array
    {
        return array_merge([
            'body' => [
                'query' => [
                    'match_all' => new \stdClass()
                ]
            ]
        ], $this->resolve($query));
    }

    public function resolveSelect(Query $query): array
    {
        $body = $this->resolve($query);

        $params = [ 'index' => $body['index'], 'type' => $body['type'] ?? ""];

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

        if (empty($body['index'])) {
            throw new InvalidIndexException("The index is missing");
        }

        return $body;
    }

    /**
     * @param Query $builder
     *
     * @return array
     */
    protected function resolveWheres(Query $builder): array
    {
        $whereGroups = $this->wherePriorityGroup($builder->wheres);

        $operation = count($whereGroups) === 1 ? 'must' : 'should';

        $bool = [];

        foreach ($whereGroups as $wheres) {
            $must = [];
            $mustNot = [];
            foreach ($wheres as $where) {
                if ($where['type'] === 'Nested') {
                    $must[] = $this->resolveWheres($where['query']);
                } else {
                    if ($where['operator'] == 'ne') {
                        $mustNot[] = $this->whereLeaf($where['leaf'], $where['column'], $where['value'], $where['operator']);
                    } else {
                        $must[] = $this->whereLeaf($where['leaf'], $where['column'], $where['value'], $where['operator']);
                    }
                }
            }

            if (!empty($must)) {
                $bool['bool'][$operation][] = count($must) === 1 ? array_shift($must) : ['bool' => ['must' => $must]];
            }
            if (!empty($mustNot)) {
                if ($operation == 'should') {
                    foreach ($mustNot as $not) {
                        $bool['bool'][$operation][] = ['bool'=>['must_not'=>$not]];
                    }
                } else {
                    $bool['bool']['must_not'] = $mustNot;
                }
            }
        }

        return $bool;
    }

    protected function whereLeaf(string $leaf, string $column, $value, string $operator = null): array
    {
        if (strpos($column, '@') !== false) {
            $columnArr = explode('@', $column);
            $ret = ['nested'=>['path'=>$columnArr[0]]];
            $ret['nested']['query']['bool']['must'][] = $this->whereLeaf($leaf, implode('.', $columnArr), $value, $operator);

            return $ret;
        }
        if (in_array($leaf, ['term', 'match', 'terms', 'match_phrase'], true)) {
            return [$leaf => [$column => $value]];
        } elseif ($leaf === 'range') {
            return [$leaf => [
                $column => is_array($value) ? $value : [$operator => $value],
            ]];
        } elseif ($leaf === 'multi_match') {
            return ['multi_match' => [
                'query'  => $value,
                'fields' => (array) $column,
                'type'   => 'phrase',
            ],
            ];
        } elseif ($leaf === 'wildcard') {
            return ['wildcard' => [
                $column => '*'.$value.'*',
            ],
            ];
        } elseif ($leaf === 'exists') {
            return ['exists' => [
                'field' => $column,
            ]];
        }
    }

    protected function wherePriorityGroup(array $wheres): array
    {
        //get "or" index from array
        $orIndex = (array) array_keys(array_map(function ($where) {
            return $where['boolean'];
        }, $wheres), 'or');

        $lastIndex = $initIndex = 0;
        $group = [];
        foreach ($orIndex as $index) {
            $group[] = array_slice($wheres, $initIndex, $index - $initIndex);
            $initIndex = $index;
            $lastIndex = $index;
        }

        $group[] = array_slice($wheres, $lastIndex);

        return $group;
    }
}