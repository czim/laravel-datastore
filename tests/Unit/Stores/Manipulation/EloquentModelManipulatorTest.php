<?php
namespace Czim\DataStore\Test\Stores\Manipulation;

use Czim\DataStore\Stores\Manipulation\EloquentModelManipulator;
use Czim\DataStore\Test\Helpers\Data\TestData;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Czim\DataStore\Test\ProvisionedTestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentModelManipulatorTest extends ProvisionedTestCase
{

    /**
     * @test
     */
    function it_takes_the_model_as_an_instance()
    {
        $manipulator = new EloquentModelManipulator;

        $model = new TestModel;

        static::assertSame($manipulator, $manipulator->setModel($model));

        static::assertSame($model, $manipulator->getModel());
    }

    /**
     * @test
     */
    function it_takes_a_config()
    {
        $manipulator = new EloquentModelManipulator;

        static::assertSame($manipulator, $manipulator->setConfig(['test' => true]));

        static::assertEquals(['test' => true], $manipulator->getConfig());
    }

    /**
     * @test
     * @depends it_takes_the_model_as_an_instance
     */
    function it_creates_a_new_model()
    {
        $manipulator = new EloquentModelManipulator;

        $manipulator->setModel(new TestModel);

        $data = new TestData(['name' => 'test model']);

        /** @var TestModel $model */
        $model = $manipulator->create($data);

        static::assertInstanceOf(TestModel::class, $model);
        static::assertTrue($model->exists);
        static::assertEquals('test model', $model->name);
    }

    /**
     * @test
     * @depends it_takes_the_model_as_an_instance
     */
    function it_makes_a_new_model_without_persisting()
    {
        $manipulator = new EloquentModelManipulator;

        $manipulator->setModel(new TestModel);

        $data = new TestData(['name' => 'test model']);

        /** @var TestModel $model */
        $model = $manipulator->make($data);

        static::assertInstanceOf(TestModel::class, $model);
        static::assertFalse($model->exists);
        static::assertEquals('test model', $model->name);
    }

    /**
     * @test
     * @depends it_takes_the_model_as_an_instance
     */
    function it_updates_an_existing_model()
    {
        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel(new TestModel);

        $model = $this->createTestModel();
        $data = new TestData(['name' => 'new name']);

        static::assertTrue($manipulator->updateById($model->getKey(), $data));

        static::assertEquals('new name', $model->fresh()->name);
    }

    /**
     * @test
     * @depends it_takes_the_model_as_an_instance
     */
    function it_throws_an_exception_if_it_cannot_find_a_model_to_update()
    {
        $this->expectException(ModelNotFoundException::class);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel(new TestModel);

        $data = new TestData(['name' => 'new name']);

        static::assertTrue($manipulator->updateById(999, $data));
    }

    /**
     * @test
     */
    function it_deletes_a_model()
    {
        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel(new TestModel);

        $model = $this->createTestModel();

        static::assertTrue($manipulator->deleteById(1));

        static::assertNull(TestModel::find($model->getKey()));
    }

}
