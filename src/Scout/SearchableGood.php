<?php

namespace Fobia\Solr\Solrquent\Scout;

/**
 * Class Searchable
 */
trait SearchableGood
{
    use Searchable;

    public function searchableAs()
    {
        return 'catalog-master';
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        return array_merge($array, [
            'name_ru_s' => $this->name
        ]);
        // return $array;
    }

    public function getScoutKey()
    {
        return $this->id;
    }
}
