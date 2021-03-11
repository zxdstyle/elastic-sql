<?php

namespace Zxdstyle\ElasticSql\Supports;

use Illuminate\Database\Eloquent\Collection;

class ResultCollection extends Collection
{
    protected $took;
    protected $timed_out;
    protected $shards;
    protected $hits;
    protected $aggregations = null;

    public function __construct(array $meta)
    {
        parent::__construct($meta);

        $this->setMeta($meta);
    }

    public function setMeta(array $meta): Result
    {
        $this->took = $meta['took'] ?? null;
        $this->timed_out = $meta['timed_out'] ?? null;
        $this->shards = $meta['_shards'] ?? null;
        $this->hits = $meta['hits'] ?? null;
        $this->aggregations = $meta['aggregations'] ?? [];

        return $this;
    }

    /**
     * Total Hits
     * @return int
     */
    public function totalHits(): int
    {
        return $this->hits['total'];
    }

    /**
     * Max Score
     * @return float
     */
    public function maxScore(): float
    {
        return $this->hits['max_score'];
    }

    /**
     * Get Shards
     * @return array
     */
    public function getShards(): array
    {
        return $this->shards;
    }

    /**
     * Took
     * @return string
     */
    public function took(): string
    {
        return $this->took;
    }

    /**
     * Timed Out
     * @return bool
     */
    public function timedOut(): bool
    {
        return (bool) $this->timed_out;
    }

    /**
     * Get Hits
     * @return array
     */
    public function getHits(): array
    {
        return $this->hits;
    }

    /**
     * Get aggregations
     * @return array
     */
    public function getAggregations(): ?array
    {
        return $this->aggregations;
    }

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
            'hits' => $this->getHits()
        ];
    }

    /**
     * Convert Elasticsearch results into an json string
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}