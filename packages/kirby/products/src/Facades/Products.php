<?php

namespace kirby\Products\Facades;

use Illuminate\Support\Facades\Facade;

class Products extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'products';
    }
}
