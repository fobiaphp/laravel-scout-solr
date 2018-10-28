<?php

namespace Fobia\Solr\Solrquent;

/**
 * Class Connection
 */
class Connection
{
    protected $pdo;

    /**
     * Connection constructor.
     */
    public function __construct()
    {
    }

    public function getPdo()
    {
        if ($this->pdo === null) {
            $this->pdo = new class ()
            {
                public function quote($value)
                {
                    return $value;
                }
            };
        }

        return $this->pdo;
    }

    /**
     * @return string
     */
    public function getDatabaseName()
    {
        return 'solr';
    }

    /**
     * @param $bindings
     * @return array
     */
    public function prepareBindings($bindings)
    {
        return (array) $bindings;
    }
}
