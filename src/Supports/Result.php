<?php

namespace Zxdstyle\ElasticSql\Supports;

class Result
{
    protected $took;
    protected $timed_out;
    protected $shards;
    protected $hits;
    protected $rawResult;
    protected $rawParams;
    protected $aggregations = null;

    public function __construct(array $meta, $rawParams)
    {
        $this->setMeta($meta);
        $this->rawParams = $rawParams;
    }

    public function setMeta(array $meta): Result
    {
        $this->rawResult = $meta ?? [];
        $this->took = $meta['took'] ?? null;
        $this->timed_out = $meta['timed_out'] ?? null;
        $this->shards = $meta['_shards'] ?? null;
        $this->hits = $meta['hits'] ?? null;
        $this->aggregations = $meta['aggregations'] ?? [];

        return $this;
    }

    /**
     * Get the original return result of the elasticsearch client
     * @return mixed
     */
    public function getRaw()
    {
        return $this->rawResult;
    }

    /**
     * Total Hits
     * @return int
     */
    public function getTotalHits(): int
    {
        $total = $this->hits['total'];
        return is_int($total) ? $total : $total['value'];
    }

    /**
     * Max Score
     * @return float
     */
    public function getMaxScore(): float
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
        $hits = $this->hits['hits'];
        foreach ($hits as &$hit) {
            $hit = $this->formatHit($hit);
        }
        return $hits;
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
        return $this->getHit();
    }

    /**
     * Convert Elasticsearch results into an json string
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function debug()
    {
        return $this->rawParams;
    }

    /**
     * @param $hit
     * @return array
     */
    protected function formatHit($hit): array
    {
        return array_merge($hit['_source'], [
            '_id' => $hit['_id'],
            '_score' => $hit['_score']
        ]);
    }

    /**
     * @return array
     */
    protected function getHit(): array
    {
        $hit = $this->hits['hits'][0];

        return $this->formatHit($hit);
    }
}