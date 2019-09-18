<?php
namespace Czim\DataStore\Test\Integration\Stores\Includes;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Resource\ResourceAdapterInterface;
use Czim\DataStore\Stores\Includes\IncludeResolver;
use Czim\DataStore\Test\Helpers\Models\TestModel;
use Czim\DataStore\Test\Helpers\Models\TestMorphRelatedModel;
use Czim\DataStore\Test\Helpers\Models\TestRelatedModel;
use Czim\DataStore\Test\ProvisionedTestCase;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class IncludeResolverTest extends ProvisionedTestCase
{

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpRelatedDatabase();
    }

    /**
     * @test
     */
    function it_resolves_nested_includes()
    {
        $resolver = new IncludeResolver;

        $model = $this->createRelatedTestModel();

        $factory = $this->getMockResourceAdapterFactory();
        $adapter = $this->getMockResourceAdapter();

        $resolver->setResourceAdapterFactory($factory);
        $resolver->setModel($model, $adapter);

        $adapter->shouldReceive('dataKeyForInclude')->once()
            ->with('related-test')
            ->andReturn('testRelatedModels');
        $adapter->shouldReceive('dataKeyForInclude')->once()
            ->with('morph-related-test')
            ->andReturn('testMorphRelatedModels');
        $adapter->shouldReceive('dataKeyForInclude')->once()
            ->with('another-test')
            ->andReturn('testParent');
        $adapter->shouldReceive('dataKeyForInclude')->once()
            ->with('deepest')
            ->andReturn('deeperNested');

        // Only one layer deep, so shouldn't need to use the adapter for this model
        $factory->shouldReceive('makeForModel')->never()->with(Mockery::type(TestRelatedModel::class));

        $factory->shouldReceive('makeForModel')->once()
            ->with(Mockery::type(TestMorphRelatedModel::class))
            ->andReturn($adapter);
        $factory->shouldReceive('makeForModel')->once()
            ->with(Mockery::type(TestModel::class))
            ->andReturn($adapter);

        $resolved = $resolver->resolve([
            'related-test',
            'morph-related-test.another-test',
            'morph-related-test.another-test.deepest',
        ]);

        static::assertEquals([
            'testRelatedModels',
            'testMorphRelatedModels.testParent.deeperNested',
        ], $resolved);
    }

    /**
     * @test
     */
    function it_ignores_includes_that_are_not_mapped_to_a_data_key()
    {
        $resolver = new IncludeResolver;

        $model = $this->createRelatedTestModel();

        $factory = $this->getMockResourceAdapterFactory();
        $adapter = $this->getMockResourceAdapter();

        $resolver->setResourceAdapterFactory($factory);
        $resolver->setModel($model, $adapter);

        $adapter->shouldReceive('dataKeyForInclude')->once()->with('related-test')->andReturn('testRelatedModels');
        $adapter->shouldReceive('dataKeyForInclude')->once()->with('morph-related-test')->andReturn(null);

        $resolved = $resolver->resolve([
            'related-test',
            'morph-related-test.another-test',
        ]);

        static::assertEquals(['testRelatedModels'], $resolved);
    }

    /**
     * @test
     */
    function it_throws_an_exception_for_includes_mapped_to_relations_of_parents_that_are_not_eloquent_relations()
    {
        $this->expectException(\RuntimeException::class);

        $resolver = new IncludeResolver;

        $model = $this->createRelatedTestModel();

        $factory = $this->getMockResourceAdapterFactory();
        $adapter = $this->getMockResourceAdapter();

        $resolver->setResourceAdapterFactory($factory);
        $resolver->setModel($model, $adapter);

        $adapter->shouldReceive('dataKeyForInclude')->once()->with('related-test')->andReturn('notARelation');

        $resolver->resolve(['related-test.nested']);
    }

    /**
     * @test
     */
    function it_rethrows_an_exception_that_was_thrown_for_includes_mapped_to_relations_of_parents_that_throw_exceptions()
    {
        $this->expectException(\RuntimeException::class);

        $resolver = new IncludeResolver;

        $model = $this->createRelatedTestModel();

        $factory = $this->getMockResourceAdapterFactory();
        $adapter = $this->getMockResourceAdapter();

        $resolver->setResourceAdapterFactory($factory);
        $resolver->setModel($model, $adapter);

        $adapter->shouldReceive('dataKeyForInclude')->once()->with('related-test')->andReturn('throwsAnException');

        $resolver->resolve(['related-test.nested']);
    }

    /**
     * @test
     */
    function it_ignores_includes_that_are_mapped_to_a_relation_without_a_resolvable_adapter()
    {
        $resolver = new IncludeResolver;

        $model = $this->createRelatedTestModel();

        $factory = $this->getMockResourceAdapterFactory();
        $adapter = $this->getMockResourceAdapter();

        $resolver->setResourceAdapterFactory($factory);
        $resolver->setModel($model, $adapter);

        $adapter->shouldReceive('dataKeyForInclude')->once()->with('related-test')->andReturn('testRelatedModels');

        $factory->shouldReceive('makeForModel')->once()->with(Mockery::type(TestRelatedModel::class))->andReturn(false);

        $resolved = $resolver->resolve([
            'related-test.test',
        ]);

        static::assertEquals(['testRelatedModels'], $resolved);
    }


    /**
     * @return Mockery\MockInterface|Mockery\Mock|ResourceAdapterFactoryInterface
     */
    protected function getMockResourceAdapterFactory()
    {
        return Mockery::mock(ResourceAdapterFactoryInterface::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|Model
     */
    protected function getMockModel()
    {
        return Mockery::mock(Model::class);
    }

    /**
     * @return Mockery\MockInterface|Mockery\Mock|ResourceAdapterInterface
     */
    protected function getMockResourceAdapter()
    {
        return Mockery::mock(ResourceAdapterInterface::class);
    }

}
