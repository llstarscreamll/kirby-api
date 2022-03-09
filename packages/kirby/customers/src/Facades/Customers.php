<?php

namespace kirby\Customers\Facades;

use Illuminate\Support\Facades\Facade;

class Customers extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'customers';
    }
}
