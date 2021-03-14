<?php

use Zxdstyle\ElasticSql\Elastic;
use PHPUnit\Framework\TestCase;

class DestroyTest extends TestCase
{
    public function testFlush()
    {
        $this->getNewClient()->index("index")->flush();

        $this->assertTrue(true);
    }

    protected function getNewClient(): Elastic
    {
        $config = [
            'hosts' => ['http://localhost:9200']
        ];
        return Elastic::Builder($config);
    }
}