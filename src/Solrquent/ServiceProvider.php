<?php

namespace Fobia\Solrquent;

use Fobia\Solrquent\ScoutSolr\ModelsResult;
use Fobia\Solrquent\ScoutSolr\SolrSearchEngine;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Solarium\Client as SolrClient;
use Solarium\Core\Client\Client;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Response;

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
            if ($app->bound(SolrSearchEngine::class)) {
                $engine = $app->make(SolrSearchEngine::class);
            }

            if (empty($engine)) {
                $client = $app->make('solrquent.solr');
                $engine = new SolrSearchEngine($client);
            }

            return $engine;
        });

        Builder::macro('getFullResult', function () {
            /** @var Builder $this */
            /** @var \Solarium\QueryType\Select\Result\Result $result */
            $result = $this->engine()->search($this);

            // $models = $this->engine()->map($this, $result, $this->model);
            // $result->models = $models;
            // return $result;

            $modelsResult = new ModelsResult($result, $this->engine(), $this);

            return $modelsResult;
        });

        Builder::macro('toSolrUrl', function () {
            /** @var Builder $self */
            /** @var \Solarium\QueryType\Select\Result\Result $result */
            $builder = $this;

            /** @var SolrSearchEngine $engine */
            $engine = $builder->engine();

            $listenerPreExecuteRequest = function ($event) {
                /** @var \Solarium\Core\Event\PreExecuteRequest $event */
                /** @var \Solarium\QueryType\Select\RequestBuilder $builder */
                /** @var \Solarium\QueryType\Select\Query\Query $query */
                $endpoint = $event->getEndpoint();
                if ($endpoint instanceof Endpoint) {
                    $endpoint = $endpoint->getCore();
                }
                $url = $event->getRequest()->getUri();

                $str = $endpoint . '/' . $url;
                $response = new Response($str, [
                    'HTTP/1.1 200 OK',
                ]);

                $event->setResponse($response);
            };

            $eventName = \Solarium\Core\Event\Events::PRE_EXECUTE_REQUEST;

            $engine->getClient()->getEventDispatcher()->addListener($eventName, $listenerPreExecuteRequest);
            $result = $engine->search($builder);
            $engine->getClient()->getEventDispatcher()->removeListener($eventName, $listenerPreExecuteRequest);

            return $result->getResponse()->getBody();
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
            if ($app->bound(Client::class)) {
                return $app->make(Client::class);
            }
            if ($app->bound(SolrClient::class)) {
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
        return ['solrquent.solr', 'solrquent.db.connection', EngineManager::class, SolrClient::class, Client::class,];
    }
}
