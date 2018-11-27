<?php

namespace Fobia\Tests;

use Fobia\Solrquent\ScoutSolr\SolrSearchEngine;
use Solarium\Client as SolrClient;

require_once __DIR__ . '/TestCase.php';

/**
 * Class ServiceProviderTest
 */
class ServiceProviderTest extends TestCase
{
    public function testSolrSearchEngine()
    {
        /** @var \Fobia\Solrquent\ScoutSolr\SolrSearchEngine $engine */
        $engine = $this->app->make(SolrSearchEngine::class);

        $this->assertInstanceOf(\Solarium\Core\Client\Client::class, $engine->getClient());
    }

    public function testSolrSearchEngineClient()
    {
        /** @var SolrClient $client */
        $config = $this->app->make('config');
        $solr = new SolrClient(['endpoint' => $config->get('solr.endpoint')]);

        $this->app->instance(SolrClient::class, $solr);
        $this->app->bind(\Solarium\Core\Client\Client::class, SolrClient::class);

        $this->assertEquals($solr, $this->app->make('solrquent.solr'));

        $client = $this->app->make(SolrClient::class);
        $engine = $this->app->make(SolrSearchEngine::class);

        $this->assertEquals($client, $this->app->make('solrquent.solr'));
        $this->assertEquals($client, $engine->getClient());
    }
}
