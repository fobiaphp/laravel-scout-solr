<?php

namespace Fobia\Solr\Solrquent\Scout;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Solarium\Core\Client\Client;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;
use Solarium\QueryType\Select\Query\Query as SelectQuery;

/**
 * Class SolrSearchEngine
 */
class SolrSearchEngine extends Engine
{

    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    // ==================================

    /*
     * ==================
     * Implements methods
     * ==================
     */
    // ===========================

    /**
     * @inheritdoc
     */
    public function update($models)
    {
        $query = $this->client->createUpdate();
        $models->each(function ($model) use (&$query) {
            $document = $query->createDocument($model->toSearchableArray());
            $query->addDocument($document);
        });

        $this->executeStatement($query);
    }

    /**
     * @inheritdoc
     */
    public function delete($models)
    {
        $query = $this->client->createUpdate();
        $models->each(function ($model) use (&$query) {
            $query->addDeleteById($model->getKey());
        });

        $this->executeStatement($query);
    }

    /**
     * @inheritdoc
     */
    public function search(Builder $builder)
    {
        $query = $this->client->createSelect();
        return $this->executeQuery($query, $builder, 0);
    }

    /**
     * @inheritdoc
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $query = $this->client->createSelect();

        $offset = ($page - 1) * $perPage;

        return $this->executeQuery($query, $builder, $offset, $perPage);
    }

    /**
     * @inheritdoc
     */
    public function mapIds($results)
    {
        return collect($results)->only('id');
    }

    /**
     * @inheritdoc
     */
    public function map(Builder $builder, $results, $model)
    {
        $map = [
            'id' => 'id'
        ]; // array_flip($model->getScoutMap());

        $models = [];
        $modelClass = get_class($model);
        // dd($results->);
        /** @var \Solarium\QueryType\Select\Result\Result $results */


        $docs = array_get($results->getData(), 'response.docs', []);
        // dump($ids);

        $keyName = $model->getKeyName();
        $docs = array_map(function($v) use ($keyName) {
            return [
                $keyName => $v['id'],
                'scout' => $v,
            ];
        }, $docs);
        return $model->hydrate($docs);

        foreach ($results as $document) {
            $attributes = [];
            foreach ($document as $field => $value) {
                $attributes[$map[$field]] = ($map[$field] != 'id') ? $value : str_replace($model->getTable() . '-', '', $value);
            }

            $models[] = new $modelClass($attributes);
        }

        return Collection::make($models);
    }

    /**
     * @inheritdoc
     */
    public function getTotalCount($results)
    {
        return $results->getNumFound();
    }

    public function flush($model)
    {
        $query = $this->client->createUpdate();
        $query->addDeleteById($model->getKey());
        $this->executeStatement($query);
    }

    // =========================================

    /**
     * Execute Select command on the index.
     *
     * @param \Solarium\QueryType\Select\Query\Query $query
     * @param \Laravel\Scout\Builder $builder
     * @param int $offset
     * @param int $limit
     * @return \Solarium\QueryType\Select\Result\Result
     */
    private function executeQuery(SelectQuery $query, Builder $builder, $offset = 0, $limit = null)
    {
        $conditions = (!empty($builder->query)) ? [$builder->query] : [];

        foreach ($builder->wheres as $key => $value) {
            $conditions[] = "$key:\"$value\"";
        }

        $query->setQuery(implode(' ', $conditions));

        if (!is_null($limit)) {
            $query->setStart($offset)
                ->setRows($limit);
        }
        // dump($query->getQuery(), $query);

        if (!empty($builder->callback)) {
            $callback = $builder->callback;
            return $callback($this->client, $query, $conditions);
        }

        $endpoint = null;

        return $this->client->select($query, $endpoint);
    }

    /**
     * Execute Update or Delete statement on the index.
     *
     * @param \Solarium\QueryType\Update\Query\Query $statement
     * @throws \Exception
     */
    private function executeStatement(UpdateQuery $statement)
    {
        $statement->addCommit();
        $result = $this->client->update($statement);

        if ($result->getStatus() != 0) {
            throw new \Exception("Update command failed.\n" . json_encode($result->getData()));
        }
    }
}
