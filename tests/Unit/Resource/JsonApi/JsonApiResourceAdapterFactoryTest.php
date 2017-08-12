<?php
namespace Czim\DataStore\Resource\JsonApi;

use Czim\DataStore\Test\TestCase;
use Czim\JsonApi\Contracts\Repositories\ResourceRepositoryInterface;
use Czim\JsonApi\Contracts\Resource\EloquentResourceInterface;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class JsonApiResourceAdapterFactoryTest extends TestCase
{

    /**
     * @test
     */
    function it_makes_an_adapter_for_a_model()
    {
        $resource = $this->getMockResource();

        $repository = $this->getMockResourceRepository();
        $repository->shouldReceive('getByModel')
            ->with(Mockery::type(Model::class))->once()
            ->andReturn($resource);

        $factory = new JsonApiResourceAdapterFactory($repository);

        /** @var Model|Mockery\MockInterface $model */
        $model = Mockery::mock(Model::class);

        $instance = $factory->makeForModel($model);

        static::assertInstanceOf(JsonApiEloquentResourceAdapter::class, $instance);
    }

    /**
     * @test
     */
    function it_makes_an_adapter_for_a_repository()
    {
        /** @var Model|Mockery\MockInterface $model */
        $model = Mockery::mock(Model::class);

        $resource = $this->getMockResource();

        $modelRepository = $this->getMockModelRepository();
        $modelRepository->shouldReceive('makeModel')->once()->andReturn($model);

        $repository = $this->getMockResourceRepository();
        $repository->shouldReceive('getByModel')
            ->with(Mockery::type(Model::class))->once()
            ->andReturn($resource);

        $factory = new JsonApiResourceAdapterFactory($repository);

        $instance = $factory->makeForRepository($modelRepository);

        static::assertInstanceOf(JsonApiEloquentResourceAdapter::class, $instance);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    function it_throws_an_exception_if_the_resource_repository_returns_an_unexpected_instance()
    {
        $repository = $this->getMockResourceRepository();
        $repository->shouldReceive('getByModel')
            ->with(Mockery::type(Model::class))->once()
            ->andReturn($this);

        $factory = new JsonApiResourceAdapterFactory($repository);

        /** @var Model|Mockery\MockInterface $model */
        $model = Mockery::mock(Model::class);

        $factory->makeForModel($model);
    }


    /**
     * @return Mockery\Mock|Mockery\MockInterface|BaseRepositoryInterface
     */
    protected function getMockModelRepository()
    {
        return Mockery::mock(BaseRepositoryInterface::class);
    }

    /**
     * @return Mockery\Mock|Mockery\MockInterface|ResourceRepositoryInterface
     */
    protected function getMockResourceRepository()
    {
        return Mockery::mock(ResourceRepositoryInterface::class);
    }

    /**
     * @return Mockery\Mock|Mockery\MockInterface|EloquentResourceInterface
     */
    protected function getMockResource()
    {
        return Mockery::mock(EloquentResourceInterface::class);
    }

}
