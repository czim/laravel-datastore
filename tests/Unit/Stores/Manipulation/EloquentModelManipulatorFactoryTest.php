<?php
namespace Czim\DataStore\Test\Stores\Manipulation;

use Czim\DataStore\Stores\Manipulation\EloquentModelManipulator;
use Czim\DataStore\Stores\Manipulation\EloquentModelManipulatorFactory;
use Czim\DataStore\Test\TestCase;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Mockery;

/**
 * Class EloquentModelManipulatorFactoryTest
 *
 * @uses \Czim\DataStore\Stores\Manipulation\EloquentModelManipulator
 */
class EloquentModelManipulatorFactoryTest extends TestCase
{

    /**
     * @test
     */
    function it_makes_a_default_manipulator_using_the_default_config()
    {
        $factory = new EloquentModelManipulatorFactory;

        /** @var Mockery\Mock|Mockery\MockInterface|Model $model */
        $model = Mockery::mock(Model::class);

        $this->app['config']->set('datastore.manipulation.config.default', ['test' => true]);

        /** @var EloquentModelManipulator $manipulator */
        $manipulator = $factory->makeForObject($model);

        static::assertInstanceOf(EloquentModelManipulator::class, $manipulator);
        static::assertSame($model, $manipulator->getModel());
        static::assertEquals(['test' => true], $manipulator->getConfig(), 'Config was not set');
    }

    /**
     * @test
     */
    function it_makes_a_specific_manipulator_using_the_default_config()
    {
        $factory = new EloquentModelManipulatorFactory;

        /** @var Mockery\Mock|Mockery\MockInterface|Model $model */
        $model = Mockery::mock(Model::class);

        $this->app['config']->set('datastore.manipulation.config.model', [
            get_class($model) => ['test' => true],
        ]);

        /** @var EloquentModelManipulator $manipulator */
        $manipulator = $factory->makeForObject($model);

        static::assertInstanceOf(EloquentModelManipulator::class, $manipulator);
        static::assertSame($model, $manipulator->getModel());
        static::assertEquals(['test' => true], $manipulator->getConfig(), 'Config was not set');
    }

    /**
     * @test
     */
    function it_makes_a_default_manipulator_for_a_repository()
    {
        $factory = new EloquentModelManipulatorFactory;

        /** @var Mockery\Mock|Mockery\MockInterface|Model $model */
        $model = Mockery::mock(Model::class);

        /** @var Mockery\Mock|Mockery\MockInterface|BaseRepositoryInterface $repository */
        $repository = Mockery::mock(BaseRepositoryInterface::class);
        $repository->shouldReceive('makeModel')->once()->andReturn($model);

        $this->app['config']->set('datastore.manipulation.config.default', ['test' => true]);

        /** @var EloquentModelManipulator $manipulator */
        $manipulator = $factory->makeForObject($repository);

        static::assertInstanceOf(EloquentModelManipulator::class, $manipulator);
        static::assertSame($model, $manipulator->getModel());
        static::assertEquals(['test' => true], $manipulator->getConfig(), 'Config was not set');
    }

}
