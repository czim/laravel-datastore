<?php
namespace Czim\DataStore\Providers;

use Czim\DataStore\Contracts\Resource\ResourceAdapterFactoryInterface;
use Czim\DataStore\Contracts\Stores\DataStoreFactoryInterface;
use Czim\DataStore\Contracts\Stores\Filtering\FilterStrategyFactoryInterface;
use Czim\DataStore\Stores\DataStoreFactory;
use Czim\DataStore\Stores\Filtering\FilterStrategyFactory;
use Illuminate\Support\ServiceProvider;

class DataStoreServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConfig();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this
            ->registerConfig()
            ->registerInterfaces();
    }


    /**
     * @return $this
     */
    protected function registerInterfaces()
    {
        $defaultDriver = $this->app['config']->get('datastore.drivers.adapter.default');

        $resourceFactoryClass = $this->app['config']->get("datastore.drivers.adapter.drivers.{$defaultDriver}.factory");

        $this->app->singleton(DataStoreFactoryInterface::class, DataStoreFactory::class);
        $this->app->singleton(FilterStrategyFactoryInterface::class, FilterStrategyFactory::class);
        $this->app->singleton(ResourceAdapterFactoryInterface::class, $resourceFactoryClass);

        return $this;
    }

    /**
     * @return $this
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/datastore.php', 'datastore');

        return $this;
    }

    /**
     * @return $this
     */
    protected function bootConfig()
    {
        $this->publishes(
            [
                realpath(__DIR__ . '/../../config/datastore.php') => config_path('datastore.php'),
            ],
            'datastore'
        );

        return $this;
    }

}
