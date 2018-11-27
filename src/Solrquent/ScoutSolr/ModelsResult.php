<?php

namespace Fobia\Solrquent\ScoutSolr;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Solarium\Exception\HttpException;
use Solarium\QueryType\Select\Result\Result;

/**
 * Class ModelsResult.
 */
class ModelsResult extends Result
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $models;

    /**
     * @var LengthAwarePaginator
     */
    protected $paginate;

    /**
     * Constructor.
     *
     * @param \Solarium\QueryType\Select\Result\Result $result
     * @param \Laravel\Scout\Engines\Engine $engine
     * @param \Laravel\Scout\Builder $builder
     *
     * @throws HttpException
     */
    public function __construct(Result $result, Engine $engine, Builder $builder)
    {
        parent::__construct($result->query, $result->response);

        $this->models = $engine->map($builder, $this, $builder->model);
    }

    public function initPaginate($perPage, $pageName = 'page', $page = null)
    {
        $results = Collection::make($this->getModels());
        $paginator = (new LengthAwarePaginator($results, $this->getNumFound(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]));

        $this->paginate = $paginator->appends('query', $this->query);
    }

    /**
     * @return LengthAwarePaginator
     */
    public function getPaginate()
    {
        return $this->paginate;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getModels()
    {
        return $this->models;
    }
}
