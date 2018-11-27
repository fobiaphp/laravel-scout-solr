<?php

namespace Fobia\Solrquent;

use Fobia\Solrquent\ScoutSolr\SolrSearchEngine;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Solarium\Client as SolrClient;
use Solarium\Core\Client\Client;

/**
 * Class ServiceProvider
 */
class ServiceProvider extends BaseServiceProvider
{
    protected $defer = true;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        resolve(EngineManager::class)->extend('solr', function ($app) {
            if ($app->has(SolrSearchEngine::class)) {
                $engine = $app->make(SolrSearchEngine::class);
            } else {
                $client = $app->make('solrquent.solr');
                $engine = new SolrSearchEngine($client);
            }

            return $engine;

            // $client = $this->app->make('solrquent.solr');
            // return new SolrSearchEngine($client);
        });

        Builder::macro('getFullResult', function () {
            /** @var Builder $this */
            /** @var \Solarium\QueryType\Select\Result\Result $result */
            $result = $this->engine()->search($this);

            $models = $this->engine()->map($this, $result, $this->model);
            $result->models = $models;
            return $result;

            // $self = $this;
            // $model = $this->model;
            // $c = function () use ($self, $result, $model) {
            //     return $this->engine()->map($this, $result, $this->model);
            // };
            //
            // $r = new ModelsResult($result, $c);
            // return $r;
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Solr client instance
        $this->app->singleton('solrquent.solr', function ($app) {
            if ($app->has(Client::class)) {
                return $app->make(Client::class);
            }
            if ($app->has(SolrClient::class)) {
                return $app->make(SolrClient::class);
            }

            $config = $app->make('config');
            $solr = new SolrClient(['endpoint' => $config->get('solr.endpoint')]);
            return $solr;
        });

        $this->app->singleton('solrquent.db.connection', function () {
            return new Connection();
        });
    }

    public function provides()
    {
        return ['solrquent.solr', 'solrquent.db.connection', EngineManager::class, SolrClient::class, Client::class, ];
    }
}
