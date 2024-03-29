<?php

namespace kirby\Machines\Facades;

use Illuminate\Support\Facades\Facade;

class Machines extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'machines';
    }
}
