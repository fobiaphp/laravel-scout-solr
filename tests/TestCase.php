<?php

namespace Fobia\Tests;

use Fobia\Solrquent\ServiceProvider;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Symfony\Component\Filesystem\Filesystem;

abstract class TestCase extends Orchestra
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    public function setUp()
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    public function tearDown()
    {
        if (!empty($this->db)) {
            $this->db->flushQueryLog();
            $this->db->disconnect();
        }

        parent::tearDown();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        $app['config']->set('queue.default', 'sync');

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            // 'database' => __DIR__ . '/database.db', // env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ]);

        $app['config']->set('scout', [
            'driver' => 'solr',
            'queue' => false,
            'chunk' => [
                'searchable' => 5,
                'unsearchable' => 5,
            ],
            'soft_delete' => false,
        ]);

        $app['config']->set('solr', [
            'endpoint' => [ // все используемые подключения
                'products' => [
                    'host' => env('SOLR_HOST', 'solr'),
                    'port' => env('SOLR_PORT', '8983'),
                    'path' => env('SOLR_PATH', '/solr'),
                    'core' => env('SOLR_MASTER_ALIAS', 'products'),
                ],
                'admin' => [
                    'host' => env('SOLR_HOST', 'solr'),
                    'port' => env('SOLR_PORT', '8983'),
                    'path' => env('SOLR_PATH', '/solr'),
                    'core' => env('SOLR_CORE_ADMIN', 'admin'),
                ],
            ],
        ]);

        $app->bind('path.public', function () {
            return $this->getTempDirectory();
        });

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app = null)
    {
        if ($app == null) {
            $app = $this->app;
        }

        if ($this->db === null) {
            $this->db = $app['db']->connection('sqlite');
        }

        try {
            $this->db->reconnect();
        } catch (\Exception $e) {
            $this->db = $app['db']->connection('sqlite');
        }

        /** @var \Illuminate\Database\DatabaseManager $db */
        $db = $this->db;
        // $statement = file_get_contents(__DIR__ . '/Fixtures/db.sql');
        // foreach (explode(';', $statement) as $sql) {
        //     try {
        //         $db->getPdo()->exec($statement);
        //     } catch (\Exception $e) {
        //         // keep
        //     }
        // }

        $db->beginTransaction();
        $db->getPdo()->exec(file_get_contents(__DIR__ . '/Fixtures/main_categories.sql'));
        $db->getPdo()->exec(file_get_contents(__DIR__ . '/Fixtures/main_products.sql'));
        $db->getPdo()->exec(file_get_contents(__DIR__ . '/Fixtures/main_offers.sql'));
        $db->commit();

        $this->db->enableQueryLog();
    }

    protected function initializeDirectory($directory)
    {
        $fs = new Filesystem();
        if ($fs->exists($directory)) {
            $fs->remove($directory);
        }
        $fs->mkdir($directory);
    }

    public function getTempDirectory($suffix = '')
    {
        return dirname(__FILE__) . '/temp' . ($suffix == '' ? '' : '/' . $suffix);
    }
}
