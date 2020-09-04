<?php

namespace kirby\Products\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade Products.
 * 
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Products extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'products';
    }
}
