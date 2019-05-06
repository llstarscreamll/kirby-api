<?php

namespace llstarscreamll\Employees\Facades;

use Illuminate\Support\Facades\Facade;

class Employees extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'employees';
    }
}
