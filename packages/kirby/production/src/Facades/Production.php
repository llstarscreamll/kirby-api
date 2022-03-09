<?php

namespace kirby\Production\Facades;

use Illuminate\Support\Facades\Facade;

class Production extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'production';
    }
}
