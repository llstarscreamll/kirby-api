<?php

namespace llstarscreamll\Authorization\Facades;

use Illuminate\Support\Facades\Facade;

class Authorization extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'authorization';
    }
}
