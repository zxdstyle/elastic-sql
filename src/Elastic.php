<?php


namespace Zxdstyle\ElasticSql;

use Closure;
use Elasticsearch\Common\Exceptions\Conflict409Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\ClientBuilder;
use Zxdstyle\ElasticSql\Exceptions\BadMethodCallException;
use Zxdstyle\ElasticSql\Exceptions\DocumentExistsException;
use Zxdstyle\ElasticSql\Exceptions\NotFoundDocumentException;
use Zxdstyle\ElasticSql\Exceptions\RunTimeException;
use Zxdstyle\ElasticSql\Supports\Paginator;
use Zxdstyle\ElasticSql\Supports\Result;

/**
 * @method Elastic index(string|array $index)
 * @method Elastic type(string $type)
 * @method Elastic limit(int $value)
 * @method Elastic take(int $value)
 * @method Elastic offset(int $value)
 * @method Elastic skip(int $value)
 * @method Elastic orderBy(string $field, $sort)
 * @method Elastic aggBy(string | array $field, $type = null)
 * @method Elastic scroll(string $scroll)
 * @method Elastic select(string |array $columns)
 * @method Elastic whereMatch($field, $value, $boolean = 'and')
 * @method Elastic orWhereMatch($field, $value, $boolean = 'or')
 * @method Elastic whereTerm($field, $value, $boolean = 'and')
 * @method Elastic whereIn($field, array $value):
 * @method Elastic orWhereIn($field, array $value): self
 * @method Elastic orWhereTerm($field, $value, $boolean = 'or')
 * @method Elastic whereRange($field, $operator = null, $value = null, $boolean = 'and')
 * @method Elastic orWhereRange($field, $operator = null, $value = null)
 * @method Elastic whereBetween($field, array $values, $boolean = 'and')
 * @method Elastic whereNotBetween($field, array $values)
 * @method Elastic orWhereBetween($field, array $values)
 * @method Elastic orWhereNotBetween(string $field, array $values)
 * @method Elastic whereExists($field, $boolean = 'and')
 * @method Elastic whereNotExists($field, $boolean = 'and')
 * @method Elastic where($column, $operator = null, $value = null, string $leaf = 'term', string $boolean = 'and')
 * @method Elastic orWhere($field, $operator = null, $value = null, $leaf = 'term')
 * @method Elastic whereNested(Closure $callback, string $boolean)
 * @method Elastic newQuery()
 * @method Elastic getElasticSearch()
 */
class Elastic
{
    protected $rawParams = [];

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Build a new instance from the provided config
     * @param array $config
     * @return Elastic
     */
    public static function Builder(array $config): Elastic
    {
        $elasticClient = ClientBuilder::fromConfig($config);

        return new Elastic(new Query(new Engine(), $elasticClient));
    }

    /**
     * Create index data, if the index does not exist, it will be created automatically
     * @param array $data
     * @param string $primaryKey
     * @return array
     * @throws RunTimeException|DocumentExistsException
     */
    public function create(array $data, $primaryKey = 'id'): array
    {
        $id = $data[$primaryKey] ?? null;

        try {
            $result = $this->runQuery(
                $this->query->getEngine()->resolveCreate($this->query, $id, $data),
                'create'
            );
        } catch (Conflict409Exception $exception) {
            throw new DocumentExistsException('Create error:'.$exception->getMessage());
        }

        if (!isset($result['result']) || $result['result'] !== 'created') {
            throw new RunTimeException('Create error');
        }

        $data['_id'] = $id;
        $data['_result'] = $result;

        return $data;
    }

    /**
     * @param $id
     * @param array $data
     * @return bool
     * @throws RunTimeException|NotFoundDocumentException
     */
    public function update($id, array $data): bool
    {
        try {
            $result = $this->runQuery($this->query->getEngine()->resolveUpdate($this->query, $id, $data), 'update');

            if (!isset($result['result']) || $result['result'] !== 'updated') {
                // noop status means that the data has not been changed
                if ($result['result'] === 'noop') {
                    return true;
                }

                throw new RunTimeException('Update error');
            }

            return true;
        } catch (Missing404Exception $exception) {
            throw new NotFoundDocumentException("Document [$id] not found");
        }
    }

    /**
     * @param $id
     * @return bool
     * @throws RunTimeException|NotFoundDocumentException
     */
    public function delete($id): bool
    {
        try {
            $result = $this->runQuery($this->query->getEngine()->resolveDelete($this->query, $id), 'delete');

            if (!isset($result['result']) || $result['result'] !== 'deleted') {
                throw new RunTimeException('Delete error');
            }

            return true;
        } catch (Missing404Exception $exception) {
            throw new NotFoundDocumentException("Document [$id] not found");
        }
    }

    /**
     * Destroy all data
     * @return mixed
     */
    public function flush()
    {
        return $this->runQuery($this->query->getEngine()->resolveFlush($this->query), 'deleteByQuery');
    }

    public function first()
    {
        $this->query->limit(1);

        return new Result($this->search(), $this->rawParams);
    }

    public function get()
    {
        return new Paginator($this->search(), $this->rawParams);
    }

    public function search(): array
    {
        return $this->runQuery($this->query->getEngine()->resolveSelect($this->query));
    }

    /**
     * @param array $params
     * @param string $method
     * @return mixed
     */
    protected function runQuery(array $params, string $method = 'search')
    {
        $this->rawParams = $params;

        $results = call_user_func([$this->query->getClient(), $method], $params);

        $this->query = $this->query->newQuery();

        return $results;
    }

    /**
     * @param string $name
     * @param $arguments
     * @return false|mixed|Elastic
     * @throws BadMethodCallException
     */
    public function __call(string $name, $arguments)
    {
        if (method_exists($this->query, $name)) {
            $query = call_user_func_array([$this->query, $name], $arguments);

            return $query instanceof $this->query ? $this : $query;
        }

        throw new BadMethodCallException("The method [$name] was not found");
    }
}