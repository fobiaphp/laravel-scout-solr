<?php

namespace Fobia\Tests;

use Fobia\Tests\Fixtures\ProductSearchable;
use Solarium\Core\Event\Events;
use Solarium\Client as SolrClient;

/**
 * Class SolrSearchTest
 */
class SolrSearchTest extends TestCase
{
    protected $mockResponses = [];

    public function setUp()
    {
        parent::setUp();
        $this->setUpSolr();
    }

    protected function setUpSolr()
    {
        $client = $this->app->make('solrquent.solr');
        /** @var \Solarium\Client $client */
        $client->getPlugin('postbigrequest');

        // $subscriber = new EventSubscriber();
        // $client->getEventDispatcher()->removeSubscriber($subscriber);
        // $client->getEventDispatcher()->addSubscriber($subscriber);

        // $client->getEventDispatcher()->addListener(Events::PRE_EXECUTE, function ($event) {
        // $client->getEventDispatcher()->addListener(Events::PRE_CREATE_RESULT, function ($event) {
        $client->getEventDispatcher()->addListener(Events::PRE_EXECUTE_REQUEST, function ($event) {
            $q = $event->getRequest()->getParams()['q'];

            if (!empty($this->mockResponses[$q])) {
                $body = $this->mockResponses[$q];
                if (is_array($body)) {
                    $body = json_encode($body);
                }
                $response = new \Solarium\Core\Client\Response($body, [
                    'HTTP/1.1 200 OK',
                ]);
                $event->setResponse($response);
            }
            return;
        });
    }

    protected function addMockResult($query, $result)
    {
        $this->mockResponses[$query] = $result;
    }

    public function testSearchString()
    {
        $this->addMockResult('name:5', <<<JSON
{
  "responseHeader":{
    "zkConnected":true,
    "status":0,
    "QTime":1,
    "params":{
      "q":"name:5"}},
  "response":{"numFound":1,"start":0,"docs":[
      {
        "id":"5",
        "name":["product 5"],
        "_version_":1617877515428167680}]
  }}
JSON
        );
        $model = new ProductSearchable();
        $bl = $model->search('name:5');

        /** @var \Illuminate\Database\Eloquent\Collection $result */
        $result = $bl->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);

        $model = $result->first();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $model);
        $this->assertTrue(in_array('Laravel\Scout\Searchable', trait_uses_recursive($model)));

        $this->assertEquals(5, $model->getScoutKey());
    }

    public function testSearchC()
    {
        $this->addMockResult('name:5', <<<JSON
{
  "responseHeader":{
    "zkConnected":true,
    "status":0,
    "QTime":1,
    "params":{
      "q":"name:5"}},
  "response":{"numFound":1,"start":0,"docs":[
      {
        "id":"5",
        "name":["product 5"],
        "_version_":1617877515428167680}]
  }}
JSON
        );

        $model = new ProductSearchable();
        /** @var \Fobia\Solrquent\ScoutSolr\SolrSearchEngine $engine */
        $engone = $model->searchableUsing();

        $solr = $this->app->make('solrquent.solr');
        /** @var SolrClient $solr */
        $select = $solr->createSelect();
        $select = $select->setQuery('name:5');

        $bl = $model->search($select);

        /** @var \Illuminate\Database\Eloquent\Collection $result */
        $result = $bl->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);

        $model = $result->first();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $model);
        $this->assertTrue(in_array('Laravel\Scout\Searchable', trait_uses_recursive($model)));

        $this->assertEquals(5, $model->getScoutKey());
    }
}
