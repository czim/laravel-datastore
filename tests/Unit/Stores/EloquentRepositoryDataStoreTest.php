<?php
namespace Czim\DataStore\Test\Unit\Stores;

use Czim\DataStore\Context\RequestContext;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Stores\EloquentRepositoryDataStore;
use Czim\DataStore\Test\ProvisionedTestCase;
use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

class EloquentRepositoryDataStoreTest extends ProvisionedTestCase
{

    /**
     * @test
     */
    function it_takes_a_repository()
    {
        $store = new EloquentRepositoryDataStore;

        $repository = $this->getMockRepository();

        static::assertSame($store, $store->setRepository($repository));

        static::assertSame($repository, $store->getRepository());
    }

    /**
     * @test
     * @depends it_takes_a_repository
     */
    function it_returns_the_repository_model()
    {
        $store = new EloquentRepositoryDataStore;

        $model = $this->createTestModel();

        $repository = $this->getMockRepository();
        $repository->shouldReceive('makeModel')->once()->with(false)->andReturn($model);

        $store->setRepository($repository);

        static::assertSame($model, $store->getModel());
    }

    /**
     * @test
     */
    function it_returns_model_by_id()
    {
        $store = new EloquentRepositoryDataStore;

        $model = $this->createTestModel();

        $repository = $this->getMockRepository();
        $repository->shouldReceive('pushCriteriaOnce')->andReturnSelf();
        $repository->shouldReceive('find')->once()->with($model->getKey())->andReturn($model);

        $store->setRepository($repository);

        static::assertSame($model, $store->getById($model->getKey()));
    }

    /**
     * @test
     */
    function it_returns_many_models_by_id()
    {
        $store = new EloquentRepositoryDataStore;

        $model = $this->createTestModel();

        /** @var Mockery\Mock|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')->once()->with('test_models.id', [1, 2])->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(new Collection);

        $repository = $this->getMockRepository();
        $repository->shouldReceive('pushCriteriaOnce')->andReturnSelf();
        $repository->shouldReceive('query')->once()->andReturn($query);
        $repository->shouldReceive('makeModel')->once()->with(false)->andReturn($model);

        $store->setRepository($repository);

        $result = $store->getManyById([1, 2]);

        static::assertInstanceOf(Collection::class, $result);
    }

    /**
     * @test
     * @uses \Czim\DataStore\Context\RequestContext
     */
    function it_returns_models_by_empty_context()
    {
        $store = new EloquentRepositoryDataStore;

        $adapter = $this->getMockAdapter();

        $model = $this->createTestModel();

        /** @var Mockery\Mock|Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('get')->once()->andReturn(new Collection);

        $repository = $this->getMockRepository();
        $repository->shouldReceive('pushCriteriaOnce')->andReturnSelf();
        $repository->shouldReceive('query')->once()->andReturn($query);

        $store->setRepository($repository);
        $store->setResourceAdapter($adapter);

        $context = new RequestContext;

        // Prepare contextual mocks
        $adapter->shouldReceive('availableFilterKeys')->once()->andReturn(['id']);
        $adapter->shouldReceive('availableSortKeys')->once()->andReturn(['id']);
        $adapter->shouldReceive('defaultFilters')->once()->andReturn([]);
        $adapter->shouldReceive('defaultSorting')->once()->andReturn([]);
        $adapter->shouldReceive('dataKeyForAttribute')->with('id')->andReturn('id');

        $result = $store->getByContext($context);

        static::assertInstanceOf(Collection::class, $result);
    }


    /**
     * @return Mockery\MockInterface|Mockery\Mock|ResourceAdapterInterface
     */
    protected function getMockAdapter()
    {
        return Mockery::mock(ResourceAdapterInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|BaseRepositoryInterface
     */
    protected function getMockRepository()
    {
        return Mockery::mock(BaseRepositoryInterface::class);
    }

}
