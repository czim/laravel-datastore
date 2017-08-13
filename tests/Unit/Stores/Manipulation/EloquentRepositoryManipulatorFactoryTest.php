<?php
namespace Czim\DataStore\Test\Stores\Manipulation;

use Czim\DataStore\Stores\Manipulation\EloquentRepositoryManipulator;
use Czim\DataStore\Stores\Manipulation\EloquentRepositoryManipulatorFactory;
use Czim\DataStore\Test\TestCase;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Mockery;

/**
 * Class EloquentRepositoryManipulatorFactoryTest
 *
 * @uses \Czim\DataStore\Stores\Manipulation\EloquentRepositoryManipulator
 */
class EloquentRepositoryManipulatorFactoryTest extends TestCase
{

    /**
     * @test
     */
    function it_makes_a_default_manipulator_using_the_default_config()
    {
        $factory = new EloquentRepositoryManipulatorFactory;

        /** @var Mockery\Mock|Mockery\MockInterface|Model $model */
        $model = Mockery::mock(Model::class);

        /** @var Mockery\Mock|Mockery\MockInterface|BaseRepositoryInterface $repository */
        $repository = Mockery::mock(BaseRepositoryInterface::class);
        $repository->shouldReceive('makeModel')->once()->andReturn($model);

        $this->app['config']->set('datastore.manipulation.config.default', ['test' => true]);

        /** @var EloquentRepositoryManipulator $manipulator */
        $manipulator = $factory->makeForObject($repository);

        static::assertInstanceOf(EloquentRepositoryManipulator::class, $manipulator);
        static::assertSame($model, $manipulator->getModel());
        static::assertSame($repository, $manipulator->getRepository());
        static::assertEquals(['test' => true], $manipulator->getConfig(), 'Config was not set');
    }

    /**
     * @test
     */
    function it_makes_a_specific_manipulator_using_the_default_config()
    {
        $factory = new EloquentRepositoryManipulatorFactory;

        /** @var Mockery\Mock|Mockery\MockInterface|Model $model */
        $model = Mockery::mock(Model::class);

        /** @var Mockery\Mock|Mockery\MockInterface|BaseRepositoryInterface $repository */
        $repository = Mockery::mock(BaseRepositoryInterface::class);
        $repository->shouldReceive('makeModel')->once()->andReturn($model);

        $this->app['config']->set('datastore.manipulation.config.model', [
            get_class($model) => ['test' => true],
        ]);

        /** @var EloquentRepositoryManipulator $manipulator */
        $manipulator = $factory->makeForObject($repository);

        static::assertInstanceOf(EloquentRepositoryManipulator::class, $manipulator);
        static::assertSame($model, $manipulator->getModel());
        static::assertSame($repository, $manipulator->getRepository());
        static::assertEquals(['test' => true], $manipulator->getConfig(), 'Config was not set');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    function it_throws_and_exception_when_attempting_to_create_for_model()
    {
        $factory = new EloquentRepositoryManipulatorFactory;

        /** @var Mockery\Mock|Mockery\MockInterface|Model $model */
        $model = Mockery::mock(Model::class);

        $factory->makeForObject($model);
    }

}
