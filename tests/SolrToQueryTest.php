<?php

namespace Fobia\Tests;

use Fobia\Solrquent\ScoutSolr\SolrSearchEngine;
use Fobia\Tests\Fixtures\ProductSearchable;
use Laravel\Scout\Builder;
use Minimalcode\Search\Criteria;
use Solarium\Client as SolrClient;
use Solarium\Core\Client\Client;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Response;

/**
 * Class SolrSearchTest
 */
class SolrToQueryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Builder::macro('toQuery', function () {
            /** @var Builder $self */
            /** @var \Solarium\QueryType\Select\Result\Result $result */
            $builder = $this;

            /** @var SolrSearchEngine $engine */
            $engine = $builder->engine();

            $endpoint = $builder->index ?: $builder->model->searchableAs();

            $listenerPreExecute = function ($event) use ($endpoint) {
                /** @var \Solarium\Core\Event\PreExecute $event */
                /** @var \Solarium\QueryType\Select\RequestBuilder $builder */
                /** @var \Solarium\QueryType\Select\Query\Query $query */
                $query = $event->getQuery();

                $query->getRequestBuilder();
                $builder = $query->getRequestBuilder();
                $url = $builder->build($query)->getUri();

                $str = $endpoint . '/' . $url;
                $response = new Response($str, [
                    'HTTP/1.1 200 OK',
                ]);

                $result = new \Solarium\QueryType\Select\Result\Result($query, $response);
                $event->setResult($result);
            };

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

            // $eventName = \Solarium\Core\Event\Events::PRE_EXECUTE;
            $eventName = \Solarium\Core\Event\Events::PRE_EXECUTE_REQUEST;
            $listener = $listenerPreExecuteRequest;

            $engine->getClient()->getEventDispatcher()->addListener($eventName, $listener);
            $result = $engine->search($builder);
            $engine->getClient()->getEventDispatcher()->removeListener($eventName, $listener);

            return $result->getResponse()->getBody();
        });
    }

    public function testToSolrUrl()
    {
        $query = 'products/select?omitHeader=true&wt=json&json.nl=flat&q=name%3A5&start=0&rows=15&fl=%2A%2Cscore';

        $model = new ProductSearchable();
        $bl = $model->search('name:5');
        $result = $bl->toSolrUrl();

        $this->assertEquals($query, $result);
    }

    public function testToQuery()
    {
        $query = 'products/select?omitHeader=true&wt=json&json.nl=flat&q=name%3A5&start=0&rows=15&fl=%2A%2Cscore';

        $model = new ProductSearchable();
        $bl = $model->search('name:5');
        $result = $bl->toQuery();

        $this->assertEquals($query, $result);
    }

    public function testSearchString()
    {
        $query = 'products/select?omitHeader=true&wt=json&json.nl=flat&q=name%3A5&start=0&rows=15&fl=%2A%2Cscore';
        $model = new ProductSearchable();
        /** @var SolrClient $solr */
        $solr = $this->app->make('solrquent.solr');

        $select = $solr->createSelect();
        $select = $select->setQuery('name:5');

        $result = $model->search($select)->toQuery();

        $this->assertEquals($query, $result);
    }

    public function testSearchCallback()
    {
        $query = 'products/select?omitHeader=true&wt=json&json.nl=flat&q=name%3A1&start=0&rows=15&fl=%2A%2Cscore';
        $model = new ProductSearchable();

        $bl = $model->search('name:5', function ($client, $query, $c) {
            /** @var \Solarium\QueryType\Select\Query\Query $query */
            /** @var \Solarium\Client $client */
            $query->setQuery('name:1');
        });

        $result = $bl->toQuery();
        $this->assertEquals($query, $result);

        $query = 'products/select?omitHeader=true&wt=json&json.nl=flat&q=name%3A1&start=0&rows=30&fl=%2A%2Cscore';
        $bl = $bl->take(30);
        $result = $bl->toQuery();

        $this->assertEquals($query, $result);
    }

    public function testSearchQuery()
    {
        $query = 'products/select?omitHeader=true&wt=json&json.nl=flat&q=name%3A1&start=0&rows=15&fl=%2A%2Cscore';
        $model = new ProductSearchable();
        /** @var SolrClient $solr */
        $solr = $this->app->make('solrquent.solr');

        $select = $solr->createSelect();
        $select->setQuery('name:1');

        $bl = $model->search($select);
        $result = $bl->toQuery();
        $this->assertEquals($query, $result);
    }

    public function testSearchCriteria()
    {
        $query = 'products/select?omitHeader=true&wt=json&json.nl=flat&q=name%3Aproduct&start=0&rows=15&fl=%2A%2Cscore';
        $model = new ProductSearchable();

        $criteria = Criteria::where('name')
            ->is('product');

        $bl = $model->search($criteria);
        $result = $bl->toQuery();
        $this->assertEquals($query, $result);
    }
}
