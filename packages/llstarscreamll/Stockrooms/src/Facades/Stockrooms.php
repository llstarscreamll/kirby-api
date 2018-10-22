<?php

namespace llstarscreamll\Stockrooms\Facades;

use Illuminate\Support\Facades\Facade;

class Stockrooms extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'stockrooms';
    }
}
