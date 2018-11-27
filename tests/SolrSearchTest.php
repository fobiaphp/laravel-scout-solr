<?php

namespace Fobia\Tests;

use Fobia\Solrquent\ScoutSolr\ModelsResult;
use Fobia\Tests\Fixtures\ProductSearchable;
use Solarium\Core\Event\Events;

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

        $this->addMockResult('name:2', <<<JSON
{
  "responseHeader":{
    "zkConnected":true,
    "status":0,
    "QTime":1,
    "params":{
      "q":"name:2"}},
  "response":{"numFound":10,"start":0,"docs":[
      {
        "id":"5",
        "name":["product 5"],
        "_version_":1617877515428167680},
        {
        "id":"1",
        "name":["product 1"],
        "_version_":1617877515428167680}]
  }}
JSON
        );
    }

    protected function setUpSolr()
    {
        $client = $this->app->make('solrquent.solr');
        /** @var \Solarium\Client $client */
        $client->getPlugin('postbigrequest');

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

    public function testPagiinate()
    {
        $docs = [];
        for ($i = 1; $i <= 10; $i++) {
            $docs[] = [
                "id" => "$i",
                "name" => ["product $i"],
                "_version_" => 1617877515428167680,
            ];
        }
        $docs = json_encode(array_slice($docs, 0, 2));
        $this->addMockResult('name:5', <<<JSON
{
  "responseHeader":{
    "zkConnected":true,
    "status":0,
    "QTime":1,
    "params":{
      "q":"name:5"}},
  "response":{"numFound":10,"start":0,"docs": $docs
  }}
JSON
        );
        $model = new ProductSearchable();
        $paginate = $model->search('name:5')->paginate(2);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginate */
        $this->assertInstanceOf('\Illuminate\Pagination\LengthAwarePaginator', $paginate);
        $this->assertCount(2, $paginate);
        $this->assertEquals(5, $paginate->lastPage());
    }

    public function testGetFullResult()
    {
        $model = new ProductSearchable();
        $result = $model->search('name:2')->getFullResult();

        /** @var ModelsResult $result */
        $this->assertInstanceOf(ModelsResult::class, $result);
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Model', $result->getModels()->first());
    }

    public function testPaginateFull()
    {
        $model = new ProductSearchable();
        $result = $model->search('name:2')->paginateFull(3);

        /** @var ModelsResult $result */
        $this->assertInstanceOf(ModelsResult::class, $result);
        $this->assertInstanceOf('\Illuminate\Pagination\LengthAwarePaginator', $result->getPaginate());
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Model', $result->getPaginate()->first());
    }
}
