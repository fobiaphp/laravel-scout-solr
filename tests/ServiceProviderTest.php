<?php

namespace Fobia\Tests;

use Fobia\Solrquent\ScoutSolr\SolrSearchEngine;
use Fobia\Solrquent\Solr\QueryType\Exec\ExecQuery;
use Solarium\Client as SolrClient;

require_once __DIR__ . '/TestCase.php';

/**
 * Class ServiceProviderTest
 */
class ServiceProviderTest extends TestCase
{
    protected function createCollection()
    {
        // http://localhost:8983/solr/admin/collections?_=1542915842468&action=DELETE&name=products&wt=json
        $solr = $this->app->make('solrquent.solr');
        /** @var \Solarium\Client $solr */
        $name = 'products';

        try {
            $query = new ExecQuery(['handler' => 'collections?action=DELETE&name=' . $name]);
            $solr->execute($query, 'admin');
        } catch (\Exception $e) {
        }

        $query = new ExecQuery(['handler' => 'collections?action=CREATE&name=' . $name . '&numShards=1']);
        $solr->execute($query, 'admin');
    }

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
