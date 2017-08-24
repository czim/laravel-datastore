<?php
namespace Czim\DataStore\Test\Unit\Stores\Includes;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Stores\Includes\IncludeResolver;
use Czim\DataStore\Test\TestCase;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class IncludeResolverTest extends TestCase
{

    /**
     * @test
     */
    function it_takes_a_resource_adapter_factory_instance()
    {
        $resolver = new IncludeResolver;

        $factory = $this->getMockResourceAdapterFactory();

        static::assertSame($resolver, $resolver->setResourceAdapterFactory($factory));
    }

    /**
     * @test
     */
    function it_takes_a_model_instance_and_adapter()
    {
        $resolver = new IncludeResolver;

        $model   = $this->getMockModel();
        $adapter = $this->getMockResourceAdapter();

        static::assertSame($resolver, $resolver->setModel($model, $adapter));
    }

    /**
     * @test
     */
    function it_takes_a_model_and_derives_the_adapter_from_the_factory()
    {
        $resolver = new IncludeResolver;

        $model   = $this->getMockModel();
        $factory = $this->getMockResourceAdapterFactory();
        $adapter = $this->getMockResourceAdapter();

        $factory->shouldReceive('makeForModel')->once()->with($model)->andReturn($adapter);

        $resolver->setResourceAdapterFactory($factory);

        static::assertSame($resolver, $resolver->setModel($model));
    }

    /**
     * @test
     */
    function it_does_not_resolve_empty_includes()
    {
        $resolver = new IncludeResolver;

        $model = $this->getMockModel();

        $factory = $this->getMockResourceAdapterFactory();
        $adapter = $this->getMockResourceAdapter();

        $resolver->setResourceAdapterFactory($factory);
        $resolver->setModel($model, $adapter);

        static::assertEquals([], $resolver->resolve([]));
    }


    /**
     * @return Mockery\MockInterface|Mockery\Mock|ResourceAdapterFactoryInterface
     */
    protected function getMockResourceAdapterFactory()
    {
        return Mockery::mock(ResourceAdapterFactoryInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|ResourceAdapterInterface
     */
    protected function getMockResourceAdapter()
    {
        return Mockery::mock(ResourceAdapterInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|Model
     */
    protected function getMockModel()
    {
        return Mockery::mock(Model::class);
    }

}
