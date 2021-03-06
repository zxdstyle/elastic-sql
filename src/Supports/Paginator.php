<?php

namespace Zxdstyle\ElasticSql\Supports;

class Paginator extends Result
{
    /**
     * Convert Elasticsearch results into an array
     * @return array
     */
    public function toArray(): array
    {
        return [
            'took' => $this->took(),
            'timed_out' => $this->timedOut(),
            'shards' => $this->getShards(),
            'hits' => $this->getHits(),
            'total_hits' => $this->getTotalHits()
        ];
    }
}