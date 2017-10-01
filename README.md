[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status](https://travis-ci.org/czim/laravel-datastore.svg?branch=master)](https://travis-ci.org/czim/laravel-datastore)
[![Coverage Status](https://coveralls.io/repos/github/czim/laravel-datastore/badge.svg?branch=master)](https://coveralls.io/github/czim/laravel-datastore?branch=master)

# Laravel Datastore

Basic datastore framework for building APIs.

This is intended to be combined with a (JSON-API) transformer/serialization layer.
 
This approach will allow you to separate responsibilities between serialization and transformation (the API representation layer) and data access abstraction.

## Disclaimer

Currently a WIP under heavy development.

## Version Compatibility

 Laravel      | Package 
:-------------|:--------
 5.3.x        | ?
 5.4.x        | ?


## Installation

Via Composer

``` bash
$ composer require czim/laravel-datastore
```

Add the `DataStoreServiceProvider` to your `config/app.php`:

``` php
Czim\DataStore\Providers\DataStoreServiceProvider::class,
```

Publish the configuration file.

``` bash
$ php artisan vendor:publish
```

### Filtering

If you intend to make use of the (default) filtering functionality of this package, you should add the [czim/laravel-filter](https://github.com/czim/laravel-filter) dependency:

```bash
$ composer require czim/laravel-filter 
```

## Documentation

This data store package is split up, responsibility-wise, into two layers: the resource adapter and the data store itself.

A data store is responsible for retrieving and manipulating data.
The resource adapter is an interface layer between the data store and the incoming and outgoing data (which can be JSON-API, or any custom transformation/formatting layer that you choose to implement).


### Data Stores

Available data stores:

- `\Czim\DataStore\Stores\EloquentDataStore`  
    Simple Model data store.

- `\Czim\DataStore\Stores\EloquentDataStore`  
    Store to use if you have a repository (`Czim\Repository\Contracts\BaseRepositoryInterface`) available.

### Resource Adapter

This package only provides a resource adapter set-up for JSON-API out of the box, expecting you to use `czim/laravel-jsonapi`.
For any other implementation, you're encouraged to write your own adapter. This package has been designed to make it easy to swap out (custom) implementations, provided some familiarity with Laravel's container and provisioning.

### Retrieval Context

The context for retrieving information (filters, sorting, pagination) is defined in interfaces. A `RequestContext` object may be filled with data in any way, and then passed into the data store to restrict or sort the results. No specific implementation is assumed for this.


### Includes

By default, the resource (adapter) and client input determine the includes that will be used for eager loading. Eager loading is then performed on the basis of simple string relation names as `with()` parameters. 

For more flexibility, it is possible to configure include decorators to further control eager loading. 
To make use of this:
 
 1. Write an implementation of `Czim\DataStore\Contracts\Stores\Includes\IncludeDecoratorInterface`.
 2. Configure this class in the `datastore.php` configuration file:
     - Either as the default include decorator, in `datastore.include.decorator.default`,
     - or mapped for a specific class under `datastore.include.decorator.model-map.<your model class>`.   

The `decorate()` method on the decorator will be fed a resolved array of dot-notated include strings, that can be manipulated and returned as desired.

Example:
```php
use Czim\DataStore\Contracts\Stores\Includes\IncludeDecoratorInterface;
use Illuminate\Database\Eloquent\Model;

class CustomIncludeDecorator implements IncludeDecoratorInterface
{
    public function setModel(Model $model)
    {
        // Ignore or store and use the model as desired. 
    }
    
    public function decorate(array $includes, $many = false)
    {
        // Replace a specific include with a closure to eager load with specific columns.
        if (in_array('someRelation', $includes)) {
            $includes = array_diff($includes, ['someRelation']);
            $includes['someRelation'] = function ($query) {
                return $query->select(['id', 'title']);
            };
        }
        
        // Never eager load a specific relation.
        $includes = array_diff($includes, ['neverEagerLoadThis.relation']);
        
        // Always eager load some specific relation.
        $includes[] = 'translations';
    
        return $includes;
    }
}
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Coen Zimmerman][link-author]
- [All Contributors][link-contributors]


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/czim/laravel-datastore.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/czim/laravel-datastore.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/czim/laravel-datastore
[link-downloads]: https://packagist.org/packages/czim/laravel-datastore
[link-author]: https://github.com/czim
[link-contributors]: ../../contributors
