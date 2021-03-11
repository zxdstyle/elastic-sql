<?php


namespace Zxdstyle\ElasticSql;


class Builder
{
    protected $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }
}