# Products

This is a JSON:API for products management written with the Laravel Framework.

## Installation

Via Composer

```bash
$ composer require kirby/products
```

## JSON:API

In pagination lists, the items per page are 10 by default. You can increase the number of items per page by using the url param `limit=X` where **X** must be a number less or equal to 100;

### Categories

A category is a way of grouping products.

#### The `Categories` object

```json
{
    "id": 1,
    "object": "Category",
    "attributes": {
        "name": "Laptops",
        "slug": "laptops",
        "position": 4,
        "active": true,
        "created_at": "2020-08-27 04:06:05",
        "updated_at": "2020-08-27 04:06:05"
    }
}
```

#### Endpoints:

-   [GET /api/v1/categories](GET_/api/v1/categories)

##### GET /api/v1/categories

Returns a paginated `Category` list resource.

<table>
    <tr>
        <th colspan="3">Parameters</th>
    </tr>
    <tr>
        <td colspan="3">
            <b>filter.active</b> | <em>bool</em> | <small>optional</small>
            <p>Filter categories than are active or inactive. <code>true</code> for active <code>false</code> for inactive.</p>
        </td>
    </tr>
    <tr>
        <td>
            <b>sort</b> | <em>string</em> |  <small>optional</small>
            <p>Default sort is by id, posible values: position</p>
        </td>
    </tr>
</table>

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

```bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todo list.

## Security

If you discover any security related issues, please email llstarscreamll@hotmail.com instead of using the issue tracker.

## Credits

-   [Johan alvarez][link-author]

## License

Please see the [license file](license.md) for more information.

[link-author]: https://github.com/llstarscreamll
