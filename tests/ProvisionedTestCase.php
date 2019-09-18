<?php
namespace Czim\DataStore\Test;

use Czim\DataStore\Providers\DataStoreServiceProvider;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Czim\DataStore\Test\Helpers\Models\TestMorphRelatedModel;
use Czim\DataStore\Test\Helpers\Models\TestRelatedModel;
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
    public function setUp(): void
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
     * Sets up the database for relational testing.
     */
    protected function setUpRelatedDatabase()
    {
        Schema::create('test_related_models', function($table) {
            /** @var Blueprint $table */
            $table->increments('id');
            $table->string('name', 255)->nullable();
            $table->integer('test_model_id')->unsigned()->nullable();
            $table->nullableTimestamps();
        });

        Schema::create('test_morph_related_models', function($table) {
            /** @var Blueprint $table */
            $table->increments('id');
            $table->string('name', 255)->nullable();
            $table->integer('morphable_id')->unsigned()->nullable();
            $table->string('morphable_type', 255)->nullable();
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

    /**
     * @return TestModel
     */
    protected function createRelatedTestModel()
    {
        /** @var TestModel $model */
        $model = TestModel::create([
            'name' => 'testing default name',
        ]);

        TestRelatedModel::create([
            'name'          => 'Related One',
            'test_model_id' => $model->id,
        ]);

        TestRelatedModel::create([
            'name'          => 'Related Two',
            'test_model_id' => $model->id,
        ]);

        /** @var TestMorphRelatedModel $morph */
        $morph = new TestMorphRelatedModel([
            'name' => 'Morph One',
        ]);

        $model->testMorphRelatedModels()->save($morph);

        return $model;
    }

}
