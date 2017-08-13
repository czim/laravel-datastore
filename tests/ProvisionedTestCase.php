<?php
namespace Czim\DataStore\Test;

use Czim\DataStore\Providers\DataStoreServiceProvider;

abstract class ProvisionedTestCase extends TestCase
{

    /**
     * {@inheritdoc}
     */
    public function getPackageProviders($app)
    {
        return [
            DataStoreServiceProvider::class,
        ];
    }

}
