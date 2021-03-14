<?php

use Zxdstyle\ElasticSql\Elastic;
use PHPUnit\Framework\TestCase;
use Zxdstyle\ElasticSql\Exceptions\DocumentExistsException;

class CreateTest extends TestCase
{
    /**
     * Test new data
     * @throws DocumentExistsException
     * @throws \Zxdstyle\ElasticSql\Exceptions\RunTimeException
     */
    public function testCreate()
    {
        $client = $this->getNewClient();

        $data = ['id' => 1, 'key' => 'test'];

        $client->index("test")->create($data);

        $client->index("test")->delete(1);

        $this->assertTrue(true);
    }

    /**
     * Test repeated creation of data
     * @throws DocumentExistsException
     * @throws \Zxdstyle\ElasticSql\Exceptions\RunTimeException
     */
    public function testRepeatCreate()
    {
        $client = $this->getNewClient()->index("test");

        $client->flush();

        $data = ['id' => 4, 'key' => 'test'];

        $client->create($data);

//        $this->expectException(DocumentExistsException::class);
//
        $client->create($data);

//        $client->delete(4);

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