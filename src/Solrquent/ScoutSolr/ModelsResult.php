<?php

namespace Fobia\Solrquent\ScoutSolr;

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

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getModels()
    {
        return $this->models;
    }
}
