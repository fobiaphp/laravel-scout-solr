<?php

namespace Fobia\Solrquent\ScoutSolr;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Solarium\Core\Client\Client;
use Solarium\QueryType\Select\Query\Query as SelectQuery;
use Solarium\QueryType\Select\Result\Result;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;

/**
 * Class SolrSearchEngine
 */
class SolrSearchEngine extends Engine
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var callable
     */
    protected $onCommitHandle;

    public function __construct(Client $client, callable $onCommitHandle = null)
    {
        $this->client = $client;
        $this->onCommitHandle = $onCommitHandle;
    }

    /**
     * @return \Solarium\Core\Client\Client
     */
    public function getClient() : Client
    {
        return $this->client;
    }

    /**
     * @return callable
     */
    public function getOnCommitHandle() : ?callable
    {
        return $this->onCommitHandle;
    }

    /**
     * @param callable $onCommitHandle
     */
    public function setOnCommitHandle(callable $onCommitHandle = null) : void
    {
        $this->onCommitHandle = $onCommitHandle;
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
        if ($models->isEmpty()) {
            return;
        }

        $endpoint = $models->first()->searchableAs();

        if ($this->usesSoftDelete($models->first()) && config('scout.soft_delete', false)) {
            $models->each->pushSoftDeleteMetadata();
        }

        $query = $this->client->createUpdate();
        // $models->each(function ($model) use (&$query) {
        //     $array = $model->toSearchableArray();
        //     $softDeleted = array_get($model->scoutMetadata(), '__soft_deleted', null);
        //     if ($softDeleted !== null) {
        //         $array['__soft_deleted'] = $softDeleted;
        //     }
        //
        //     // Or Algolia example
        //     // $array = array_merge(
        //     //     $model->toSearchableArray(), $model->scoutMetadata()
        //     // );
        //     // $array = array_merge(['id' => $model->getScoutKey()], $array);
        //
        //     $document = $query->createDocument($array);
        //     $query->addDocument($document);
        // });

        $modelsRelations = [];
        // while ($model = $models->shift()) {
        while (!$models->isEmpty()) {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = $models->shift();

            $array = $model->toSearchableArray();
            $softDeleted = array_get($model->scoutMetadata(), '__soft_deleted', null);
            if ($softDeleted !== null) {
                $array['__soft_deleted'] = $softDeleted;
            }

            // Or Algolia example
            // $array = array_merge(
            //     $model->toSearchableArray(), $model->scoutMetadata()
            // );

            // Первичный ключ id для Solr
            $array = array_merge($array, ['id' => $model->getScoutKey()]);

            $array = array_filter($array, function ($val) {
                return !is_null($val);
            });

            $document = $query->createDocument($array);
            $query->addDocument($document);

            // Загрузка новых отношений в колекции
            $relations = array_diff_key(array_dot($this->getRelations($model)), $modelsRelations);
            if (count($relations)) {
                $modelsRelations = array_merge($modelsRelations, $relations);
                $relations = array_keys($relations);

                $models->loadMissing(...$relations);
            }
        }

        $this->executeStatement($query, $endpoint);
    }

    /**
     * @inheritdoc
     */
    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }
        $endpoint = $models->first()->searchableAs();

        $query = $this->client->createUpdate();
        $models->each(function ($model) use (&$query) {
            $query->addDeleteById($model->getScoutKey());
        });

        $this->executeStatement($query, $endpoint);
    }

    /**
     * @inheritdoc
     */
    public function search(Builder $builder)
    {
        $query = $this->client->createSelect();
        return $this->executeQuery($query, $builder, 0, $builder->limit ?: $builder->model->getPerPage());
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
        /** @var \Solarium\QueryType\Select\Result\Result $results */
        if (count($results->getDocuments()) === 0) {
            return Collection::make();
        }
        $docs = Collection::make($results->getDocuments());

        $models = $model->getScoutModelsByIds(
            $builder,
            $docs->pluck('id')->values()->all()
        )->keyBy(function ($model) {
            return $model->getScoutKey();
        });

        return $docs->map(function ($hit) use ($models) {
            if (isset($models[$hit['id']])) {
                return $models[$hit['id']];
            }
        })->filter()->values();

        // $models->each(function ($model) use ($docs) {
        //     /** @var \Illuminate\Database\Eloquent\Model $model */
        //     $solrDock = $docs->firstWhere('id', '=', $model->getScoutKey());
        //     $model->setRelation('solr', new Fluent($solrDock));
        // });
        //
        // return $models;
    }

    /**
     * @inheritdoc
     */
    public function getTotalCount($results)
    {
        return $results->getNumFound();
    }

    /**
     * @inheritdoc
     */
    public function flush($model)
    {
        $endpoint = $model->searchableAs();

        $query = $this->client->createUpdate();
        $query->addDeleteQuery('*:*');
        // $this->executeStatement($query, $endpoint);

        $query->addCommit();
        $result = $this->client->update($query, $endpoint);
        if ($result->getStatus() != 0) {
            throw new \Exception("Update command failed.\n" . json_encode($result->getData()));
        }
    }

    // =========================================

    /**
     * Возвращает ключи загруженных отношений
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function getRelations(\Illuminate\Database\Eloquent\Model $model)
    {
        $relations = collect($model->getRelations())->except('pivot')->all();

        foreach ($relations as $key => $model) {
            $relations[$key] = true;

            if ($model instanceof Collection) {
                $model = $model->first();
            }

            if ($model instanceof \Illuminate\Database\Eloquent\Model) {
                $relations[$key] = $this->getRelations($model);
                if (!count($relations[$key])) {
                    $relations[$key] = true;
                }
            }
        }

        return $relations;
    }

    /**
     * Execute Select command on the index.
     *
     * @param \Solarium\QueryType\Select\Query\Query $query
     * @param \Laravel\Scout\Builder $builder
     * @param int $offset
     * @param int $limit
     * @return \Solarium\QueryType\Select\Result\Result
     */
    protected function executeQuery(SelectQuery $query, Builder $builder, $offset = 0, $limit = null)
    {
        $endpoint = $builder->index ?: $builder->model->searchableAs();

        $conditions = [];
        if (!empty($builder->query)) {
            $searchQuery = $builder->query;

            if ($searchQuery instanceof \Solarium\Core\Query\QueryInterface) {
                $query = $searchQuery;
            } elseif ($searchQuery instanceof \Illuminate\Database\Query\Expression) {
                $searchQuery = $searchQuery->getValue();
            } elseif ($searchQuery instanceof \Minimalcode\Search\Criteria) {
                $searchQuery = $searchQuery->getQuery();
            } else {
                $searchQuery = $searchQuery;
            }

            if ($searchQuery && is_string($searchQuery)) {
                $conditions[] = $searchQuery;
            }
        }

        // $specChar = ['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '/', '\\', ];

        foreach ($builder->wheres as $key => $value) {
            if ($value instanceof \Illuminate\Database\Query\Expression) {
                $conditions[] = "$key:" . $value->__toString();
            } else {
                $conditions[] = "$key:\"$value\"";
            }
        }

        if (count($conditions)) {
            $query->setQuery(implode(' ', $conditions));
        }

        if (!is_null($limit)) {
            $query->setStart($offset)
                ->setRows($limit);
        }

        if (!empty($builder->callback)) {
            $callback = $builder->callback;
            $result = $callback($this->client, $query, $conditions);
            if ($result instanceof Result) {
                return $result;
            }
        }

        $result = $this->client->select($query, $endpoint);

        return $result;
    }

    /**
     * Execute Update or Delete statement on the index.
     *
     * @param \Solarium\QueryType\Update\Query\Query $statement
     * @param string|null $endpoint
     * @throws \Exception
     */
    protected function executeStatement(UpdateQuery $statement, $endpoint = null)
    {
        if ($this->onCommitHandle !== null) {
            $handle = $this->onCommitHandle;
            $handle($statement);
        } else {
            $statement->addCommit();
        }

        $result = $this->client->update($statement, $endpoint);

        if ($result->getStatus() != 0) {
            throw new \Exception("Update command failed.\n" . json_encode($result->getData()));
        }
    }

    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }
}
