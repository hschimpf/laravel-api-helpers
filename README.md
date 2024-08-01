# Laravel API Helpers

This library simplifies the process of building API controllers by providing convenient classes for managing filtering, ordering, relationship loading, and pagination of resource collections.

[![Latest stable version](https://img.shields.io/packagist/v/hds-solutions/laravel-api-helpers?style=flat-square&label=latest&color=0092CB)](https://github.com/hschimpf/laravel-api-helpers/releases/latest)
[![License](https://img.shields.io/github/license/hschimpf/laravel-api-helpers?style=flat-square&color=009664)](https://github.com/hschimpf/laravel-api-helpers/blob/main/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/hds-solutions/laravel-api-helpers?style=flat-square&color=747474)](https://packagist.org/packages/hds-solutions/laravel-api-helpers)
[![Monthly Downloads](https://img.shields.io/packagist/dm/hds-solutions/laravel-api-helpers?style=flat-square&color=747474&label=)](https://packagist.org/packages/hds-solutions/laravel-api-helpers)
[![Required PHP version](https://img.shields.io/packagist/dependency-v/hds-solutions/laravel-api-helpers/php?style=flat-square&color=006496&logo=php&logoColor=white)](https://packagist.org/packages/hds-solutions/laravel-api-helpers)

## Features

- Easy management of request query filters for filtering resource collections based on allowed columns.
- Simplified sorting of resource collections based on allowed columns.
- Convenient loading of extra relationships for resource collections.
- Pagination support for resource collections.

## Installation

### Dependencies

- PHP >= 8.0
- Laravel Framework >= 9.0

### Via composer

```bash
composer require hds-solutions/laravel-api-helpers
```

## Usage

To make use of the library, you will need to create specific classes that extend the provided abstract classes.
The provided classes contain the implementation of the necessary logic for each feature (filtering, sorting, relationship loading, and pagination).

### ResourceFilters

The `ResourceFilters` class manages the query filters for resource collections.
It allows you to define the allowed columns and their corresponding filter operators.

In the extended class, you can define the list of allowed columns that can be used for filtering, along with their allowed operators.

The available operators are:

- `eq`: Translates to a `field_name = "value"` filter.
- `ne`: Translates to a `field_name != "value"` filter.
- `has`: Translates to a `field_name LIKE "%value%"` filter.
- `lt`: Translates to a `field_name < "value"` filter.
- `lte`: Translates to a `field_name <= "value"` filter.
- `gt`: Translates to a `field_name > "value"` filter.
- `gte`: Translates to a `field_name >= "value"` filter.
- `in`: Translates to a `field_name IN ("value1", "value2", ...)` filter.
- `btw`: Translates to a `field_name BETWEEN "value1" AND "value2"` filter.

Operators are also grouped by field type:

- `string`: Translates to the operators `eq`, `ne` and `has`.
- `numeric`: Translates to the operators `eq`, `ne`, `lt`, `lte`, `gt`, `gte`, `in`, and `btw`.
- `boolean`: Translates to the operators `eq` and `ne`.
- `date`: Translates to the operators `eq`, `ne`, `lt`, `lte`, `gt`, `gte`, and `btw`.

#### Example implementation

You just need to extend the `ResourceFilters` class and define the allowed filtrable columns.

```php
namespace App\Http\Filters;

class CountryFilters extends \HDSSolutions\Laravel\API\ResourceFilters {

    protected array $allowed_columns = [
        'name'      => 'string',
        'code'      => 'string',
        'size_km2'  => [ 'gt', 'lt', 'btw' ],
    ];

}
```

You can also override the default filtering implementation of a column by defining a method with the same name as the filtrable column.
The method **must** have the following arguments:

- `Illuminate\Database\Eloquent\Builder`: The current instance of the query builder.
- `string`: The operator requested for filtering.
- `mixed`: The value of the filter.

```php
namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

class CountryFilters extends \HDSSolutions\Laravel\API\ResourceFilters {

    protected array $allowed_columns = [
        'name'          => 'string',
        'code'          => 'string',
        'size_km2'      => [ 'gt', 'lt', 'btw' ],
        'regions_count' => 'number',
    ];
    
    protected function regionsCount(Builder $query, string $operator, $value): void {
        return $query->whereHas('regions', operator: $operator, count: $value);
    }

}
```

#### Example requests

- Filtering by country name:

    ```http request
    GET https://localhost/api/countries?name[has]=aus
    Accept: application/json
    ```
    Example response:
    ```json5
    {
        "data": [
            {
                "id": 123,
                "name": "Country name",
                "size_km2": 125000,
                ...
            },
            { ... },
            { ... },
            { ... },
            ...
        ],
        "links": {
            ...
        }
        "meta": {
            ...
        }
    }
    ```

- Filtering by country size:

    ```http request
    GET https://localhost/api/countries?size_km2[btw]=100000,500000
    Accept: application/json
    ```
    Example response:
    ```json5
    {
        "data": [
            {
                "id": 123,
                "name": "Country name",
                "size_km2": 125000,
                ...
            },
            { ... },
            { ... },
            { ... },
            ...
        ],
        "links": {
            ...
        }
        "meta": {
            ...
        }
    }
    ```

- Filtering by countries that have more than N regions:

    ```http request
    GET https://localhost/api/countries?regions_count[gte]=15
    Accept: application/json
    ```
    Example response:
    ```json5
    {
        "data": [
            {
                "id": 123,
                "name": "Country name",
                "size_km2": 125000,
                ...
            },
            { ... },
            { ... },
            { ... },
            ...
        ],
        "links": {
            ...
        }
        "meta": {
            ...
        }
    }
    ```

### ResourceOrders

The `ResourceOrders` class manages the sorting of resource collections.
It allows you to define the allowed columns to sort the resource collection and a default sorting fields.

In the extended class, you can define the list of allowed columns that can be used for sorting the resource collection.

#### Example implementation

You just need to extend the `ResourceOrders` class and define the allowed sortable columns.

```php
namespace App/Http/Orders;

class CountryOrders extends \HDSSolutions\Laravel\API\ResourceOrders {

    protected array $default_order = [
        'name',
    ];

    protected array $allowed_columns = [
        'name',
    ];

}
```

You can also override the default sorting implementation of a column by defining a method with the studly version of the sortable column.
The method **must** have the following arguments:

- `Illuminate\Database\Eloquent\Builder`: The current instance of the query builder.
- `string`: The direction of the sort.

```php
namespace App/Http/Orders;

use Illuminate\Database\Eloquent\Builder;

class CountryOrders extends \HDSSolutions\Laravel\API\ResourceOrders {

    protected array $default_order = [
        'name',
    ];

    protected array $allowed_columns = [
        'name',
        'regions_count',
    ];
    
    protected function regionsCount(Builder $query, string $direction): void {
        $query->orderBy('regions_count', direction: $direction);
    }

}
```

#### Example requests
The request sorting parameters must follow the following syntax: `order[{index}][{direction}]={field}`

- Sorting by country name:

    ```http request
    GET https://localhost/api/countries?order[0][asc]=name
    Accept: application/json
    ```
    Example response:
    ```json5
    {
        "data": [
            {
                "id": 123,
                "name": "Country name",
                ...
            },
            { ... },
            { ... },
            { ... },
            ...
        ],
        "links": {
            ...
        }
        "meta": {
            ...
        }
    }
    ```

- Sorting by country name and regions count in descending order:

    ```http request
    GET https://localhost/api/countries?order[0][asc]=name&order[1][desc]=regions_count
    Accept: application/json
    ```
    Example response:
    ```json5
    {
        "data": [
            {
                "id": 123,
                "name": "Country name",
                ...
            },
            { ... },
            { ... },
            { ... },
            ...
        ],
        "links": {
            ...
        }
        "meta": {
            ...
        }
    }
    ```

### ResourceRelations
The `ResourceRelations` class manages the loading of extra relationships for resource collections.
It allows you to specify the allowed relationships to be loaded and the relationships that should always be loaded.

In the extended class, you can define the list of allowed relationships that can be added to the resource collection.

#### Example implementation

```php
namespace App/Http/Relations;

class CountryRelations extends \HDSSolutions\Laravel\API\ResourceRelations {

    protected array $with_count = [
        'regions',
    ];

    protected array $allowed_relations = [
        'regions',
    ];

}
```

You can also capture the loaded relationship to add filters, sorting, or any action that you need.
The method **must** have the following arguments:

- `Illuminate\Database\Eloquent\Relations\Relation`: The instance of the relationship being loaded.

```php
namespace App/Http/Relations;

class CountryRelations extends \HDSSolutions\Laravel\API\ResourceRelations {

    protected array $with_count = [
        'regions',
    ];

    protected array $allowed_relations = [
        'regions',
    ];
    
    protected function regions(Relation $regions): void {
        $regions->where('active', true);
    }

}
```

#### Example requests
- Loading countries with their regions relationship collection:

    ```http request
    GET https://localhost/api/countries?with[]=regions
    Accept: application/json
    ```
    Example response:
    ```json5
    {
        "data": [
            {
                "id": 123,
                "name": "Country name",
                "regions_count": 5,
                "regions": [
                    { ... },
                    { ... },
                    { ... },
                    ...
                ]
            },
            { ... },
            { ... },
            { ... },
            ...
        ],
        "links": {
            ...
        }
        "meta": {
            ...
        }
    }
    ```

### PaginateResults
The `PaginateResults` class handles the pagination of resource collections.
It provides support for paginating the results or retrieving all records.

#### Example requests
- Request all countries:
    ```http
    GET https://localhost/api/countries?all=true
    Accept: application/json
    ```
    Example response:
    ```json5
    {
        "data": [
            {
                "id": 123,
                "name": "Country name",
                "regions_count": 5
            },
            { ... },
            { ... },
            { ... },
            { ... },
            { ... },
            { ... },
            { ... },
            { ... },
            { ... },
            { ... },
            { ... }
        ]
    }
    ```

### Controller implementation
Here is an example of a controller using the `Pipeline` facade to implement all the previous features.

```php
namespace App/Http/Controllers/Api;

use App\Models\Country;

use App\Http\Filters;
use App\Http\Relations;
use App\Http\Orders;

use HDSSolutions\Laravel\API\Actions\PaginateResults;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Pipeline;

class CountryController extends Controller {

    public function index(Request $request): ResourceCollection {
        return new ResourceCollection(
            Pipeline::send(Country::query())
                ->through([
                    Filters\CountryFilters::class,
                    Relations\CountryRelations::class,
                    Orders\CountryOrders::class,
                    PaginateResults::class,
                ])
                ->thenReturn()
        );
    }
    
    public function show(Request $request, int $country_id): JsonResource {
        return new Resource(
            Pipeline::send(Country::where('id', $country_id))
                ->through([
                    Relations\CountryRelations::class,
                ])
                ->thenReturn()
                ->firstOrFail()
            )
        );
    }

}
```

## More request examples
```http request
GET https://localhost/api/regions
Accept: application/json
```
Example response:
```json5
{
    "data": [
        {
            "id": 5,
            "name": "Argentina",
            "code": "AR",
            "regions_count": 24
        },
        {
            "id": 1,
            "name": "Canada",
            "code": "CA",
            "regions_count": 13
        },
        {
            "id": 3,
            "name": "Germany",
            "code": "DE",
            "regions_count": 16
        },
        ...
    ],
    "links": {
        "first": "https://localhost/api/regions?page=1",
        "last": "https://localhost/api/regions?page=13",
        "prev": null,
        "next": "https://localhost/api/regions?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 13,
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "https://localhost/api/regions?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "https://localhost/api/regions?page=2",
                "label": "2",
                "active": false
            },
            {
                "url": null,
                "label": "...",
                "active": false
            },
            {
                "url": "https://localhost/api/regions?page=12",
                "label": "12",
                "active": false
            },
            {
                "url": "https://localhost/api/regions?page=13",
                "label": "13",
                "active": false
            },
            {
                "url": "https://localhost/api/regions?page=2",
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "path": "https://localhost/api/regions",
        "per_page": 15,
        "to": 15,
        "total": 195
    }
}
```

```http request
GET https://localhost/api/regions?name[has]=aus
Accept: application/json
```
Example response:
```json5
{
    "data": [
        {
            "id": 34,
            "name": "Australia",
            "code": "AU",
            "regions_count": 8
        },
        {
            "id": 12,
            "name": "Austria",
            "code": "AT",
            "regions_count": 9
        }
    ],
    "links": {
        ...
    },
    "meta": {
        ...
    }
}
```

```http request
GET https://localhost/api/regions?regions_count[gt]=15&order[][desc]=name
Accept: application/json
```
Example response:
```json5
{
    "data": [
        ...
        {
            "id": 3,
            "name": "Germany",
            "code": "DE",
            "regions_count": 16
        },
        {
            "id": 5,
            "name": "Argentina",
            "code": "AR",
            "regions_count": 24
        },
        ...
    ],
    "links": {
        ...
    },
    "meta": {
        ...
    }
}
```

## Extras
### Before and after callbacks
The `ResourceFilters` and `ResourceOrders` classes have two methods (`before` & `after`) that allow a better customization on the query builder. You can override them to make more manipulations to the query builder.

### ResourceRequest
The `ResourceRequest` class has the following features:

- The `hash()` method gives you a unique identifier based on the query parameters.
- The `authorize()` method is a WIP feature that will handle resource access authorization.

### Caching requests
You can use the `hash()` method of the `ResourceRequest` class and use it as a cache key. The parameter `cache` is ignored and not used to build the request identifier.

In the following example, we capture the `cache` request parameter to force the cache to be cleared.

```php
namespace App/Http/Controllers/Api;

use App\Models\Country;

use App\Http\Filters;
use App\Http\Relations;
use App\Http\Orders;

use HDSSolutions\Laravel\API\Actions\PaginateResults;
use HDSSolutions\Laravel\API\ResourceRequest;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Pipeline;

class CountryController extends Controller {

    public function index(ResourceRequest $request): JsonResponse | ResourceCollection {
        // forget cached data if is requested
        if ($request->boolean('cache', true) === false) {
            cache()->forget($request->hash(__METHOD__));
        }

        // remember data for 8 hours, using request unique hash as cache key
        return cache()->remember(
            key: $request->hash(__METHOD__),
            ttl: new DateInterval('PT8H'),
            callback: fn() => (new ResourceCollection($request,
                Pipeline::send(Country::query())
                    ->through([
                        Filters\CountryFilters::class,
                        Relations\CountryRelations::class,
                        Orders\CountryOrders::class,
                        PaginateResults::class,
                    ])
                    ->thenReturn()
                )
            )->response($request)
        );
    }

    public function show(Request $request, int $country_id): JsonResponse | JsonResource {
        if ($request->boolean('cache', true) === false) {
            cache()->forget($request->hash(__METHOD__));
        }

        return cache()->remember(
            key: $request->hash(__METHOD__),
            ttl: new DateInterval('PT8H'),
            callback: fn() => (new Resource(
                Pipeline::send(Model::where('id', $country_id))
                    ->through([
                        Relations\CountryRelations::class,
                    ])
                    ->thenReturn()
                    ->firstOrFail()
                )
            )->response($request)
        );
    }

}
```

# Security Vulnerabilities
If you encounter any security-related issues, please feel free to raise a ticket on the issue tracker.

# Contributing
Contributions are welcome! If you find any issues or would like to add new features or improvements, please feel free to submit a pull request.

## Contributors
- [Hermann D. Schimpf](https://hds-solutions.net)

# Licence
This library is open-source software licensed under the [MIT License](LICENSE).
Please see the [License File](LICENSE) for more information.
