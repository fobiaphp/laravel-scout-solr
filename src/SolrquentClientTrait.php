<?php

namespace Fobia\Solr\Solrquent;

use Illuminate\Support\Facades\App;
use Solarium\Client as SolrClient;

trait SolrquentClientTrait
{
    /**
     * @var SolrClient
     */
    protected $solrClient;

    /**
     * @return \Solarium\Client
     */
    public function getSolrClient()
    {
        if ($this->solrClient === null) {
            $this->solrClient = App::make('solrquent.solr');
        }

        return $this->solrClient;
    }

    /**
     * @param \Solarium\Client $solrClient
     */
    public function setSolrClient(\Solarium\Client $solrClient)
    {
        $this->solrClient = $solrClient;
    }

}
