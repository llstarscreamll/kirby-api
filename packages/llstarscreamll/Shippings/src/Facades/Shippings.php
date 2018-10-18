<?php

namespace llstarscreamll\Shippings\Facades;

use Illuminate\Support\Facades\Facade;

class Shippings extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'shippings';
    }
}
