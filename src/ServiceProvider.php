<?php

namespace Fobia\Solr\Solrquent;

use Fobia\Solr\Solrquent\Scout\SolrSearchEngine;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Laravel\Scout\EngineManager;
use Solarium\Client as SolrClient;

/**
 * Class ServiceProvider
 */
class ServiceProvider extends BaseServiceProvider
{
    // protected $defer = true;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

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
            return $this->app->make(SolrClient::class);
            $config = $app->make('config');
            $connectionName = $config->get('database.solr.connection', 'Solarium\Client');
            return $app->make($connectionName);
        });

        $this->app->singleton('solrquent.db.connection', function () {
            return new Connection();
        });

        resolve(EngineManager::class)->extend('solr', function () {
            $client = $this->app->make('solrquent.solr');
            return new SolrSearchEngine($client);
        });

    }

    public function provides()
    {
        return ['solrquent.solr', 'solrquent.db.connection', EngineManager::class];
    }
}
