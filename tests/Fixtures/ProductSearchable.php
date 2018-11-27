<?php

namespace Fobia\Tests\Fixtures;

use Laravel\Scout\Searchable;

/**
 * Class Product
 */
class ProductSearchable extends Product
{
    use Searchable;

    public function searchableAs()
    {
        return 'products';
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        return array_merge([], [
            // 'name_ru_s' => $array['name'], //$this->name
            'id' => $array['id'],
            'name' => $array['name'], //$this->name
        ]);
        // return $array;
    }

    public function getScoutKey()
    {
        return $this->id;
    }

    public function getScoutKeyName()
    {
        return 'id';
    }
}
