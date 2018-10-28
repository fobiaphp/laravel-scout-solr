<?php

namespace Fobia\Solr\Solrquent\Scout;

use Fobia\Solr\Solrquent\SolrquentClientTrait;
use Laravel\Scout\Searchable as BaseSearchable;

/**
 * Class Searchable
 * @package App\Lib\Solr\Solrquent
 */
trait Searchable
{
    use BaseSearchable;
    use SolrquentClientTrait;
}
