<?php
namespace Czim\DataStore\Test;

use Czim\DataStore\Providers\DataStoreServiceProvider;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class ProvisionedTestCase extends TestCase
{

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('paperclip', include(realpath(dirname(__DIR__) . '/config/datastore.php')));

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', $this->getDatabaseConfigForSqlite());
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageProviders($app)
    {
        return [
            DataStoreServiceProvider::class,
        ];
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Returns the testing config for a (shared) SQLite connection.
     *
     * @return array
     */
    protected function getDatabaseConfigForSqlite()
    {
        return [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ];
    }

    /**
     * Sets up the database for testing.
     */
    protected function setUpDatabase()
    {
        Schema::create('test_models', function($table) {
            /** @var Blueprint $table */
            $table->increments('id');
            $table->string('name', 255)->nullable();
            $table->nullableTimestamps();
        });
    }

    /**
     * @return TestModel
     */
    protected function createTestModel()
    {
        return TestModel::create([
            'name' => 'testing default name',
        ]);
    }

}
