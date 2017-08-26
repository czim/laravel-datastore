<?php
namespace Czim\DataStore\Test\Stores\Manipulation;

use Czim\DataStore\Stores\Manipulation\EloquentRepositoryManipulator;
use Czim\DataStore\Test\Helpers\Data\TestData;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Czim\DataStore\Test\ProvisionedTestCase;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Mockery;

class EloquentRepositoryManipulatorTest extends ProvisionedTestCase
{

    /**
     * @test
     */
    function it_takes_the_model_as_an_instance()
    {
        $manipulator = new EloquentRepositoryManipulator;

        $model = new TestModel;

        static::assertSame($manipulator, $manipulator->setModel($model));

        static::assertSame($model, $manipulator->getModel());
    }

    /**
     * @test
     */
    function it_takes_a_config()
    {
        $manipulator = new EloquentRepositoryManipulator;

        static::assertSame($manipulator, $manipulator->setConfig(['test' => true]));

        static::assertEquals(['test' => true], $manipulator->getConfig());
    }

    /**
     * @test
     */
    function it_takes_a_repository()
    {
        $manipulator = new EloquentRepositoryManipulator;

        $repository = $this->getMockRepository();

        static::assertSame($manipulator, $manipulator->setRepository($repository));

        static::assertSame($repository, $manipulator->getRepository());
    }

    /**
     * @test
     * @depends it_takes_the_model_as_an_instance
     */
    function it_creates_a_new_model()
    {
        $manipulator = new EloquentRepositoryManipulator;

        $repository = $this->getMockRepository();

        $manipulator->setModel(new TestModel);
        $manipulator->setRepository($repository);

        $data = new TestData(['name' => 'test model']);

        $model = $this->createTestModel();

        $repository->shouldReceive('create')->once()
            ->with(['name' => 'test model'])
            ->andReturn($model);

        /** @var TestModel $model */
        $createdModel = $manipulator->create($data);

        static::assertInstanceOf(TestModel::class, $createdModel);
    }

    /**
     * @test
     * @depends it_takes_the_model_as_an_instance
     */
    function it_make_a_new_model_without_persisting()
    {
        $manipulator = new EloquentRepositoryManipulator;

        $repository = $this->getMockRepository();

        $manipulator->setModel(new TestModel);
        $manipulator->setRepository($repository);

        /** @var TestModel $model */
        $model = new TestModel;
        $data  = new TestData(['name' => 'test model']);

        $repository->shouldReceive('makeModel')->once()->with(false)->andReturn($model);

        /** @var TestModel $model */
        $createdModel = $manipulator->make($data);

        static::assertSame($model, $createdModel);
        static::assertEquals('test model', $model->name);
    }

    /**
     * @test
     * @depends it_takes_the_model_as_an_instance
     */
    function it_updates_an_existing_model()
    {
        $manipulator = new EloquentRepositoryManipulator;

        $repository = $this->getMockRepository();

        $manipulator->setModel(new TestModel);
        $manipulator->setRepository($repository);

        $model = $this->createTestModel();

        $repository->shouldReceive('update')->once()
            ->with(['name' => 'new name'], $model->getKey())
            ->andReturn(true);

        $data = new TestData(['name' => 'new name']);

        static::assertTrue($manipulator->updateById($model->getKey(), $data));
    }
    
    /**
     * @test
     */
    function it_deletes_a_model()
    {
        $manipulator = new EloquentRepositoryManipulator;

        $repository = $this->getMockRepository();

        $manipulator->setModel(new TestModel);
        $manipulator->setRepository($repository);

        $model = $this->createTestModel();

        $repository->shouldReceive('delete')->once()
            ->with($model->getKey())
            ->andReturn(true);

        static::assertTrue($manipulator->deleteById(1));
    }


    /**
     * @return Mockery\MockInterface|Mockery\Mock|BaseRepositoryInterface
     */
    protected function getMockRepository()
    {
        return Mockery::mock(BaseRepositoryInterface::class);
    }

}
