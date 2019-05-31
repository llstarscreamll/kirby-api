<?php

namespace llstarscreamll\Novelties\Facades;

use Illuminate\Support\Facades\Facade;

class Novelties extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'novelties';
    }
}
