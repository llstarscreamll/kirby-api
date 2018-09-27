<?php

namespace llstarscreamll\Authentication\Facades;

use Illuminate\Support\Facades\Facade;

class Authentication extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'authentication';
    }
}
