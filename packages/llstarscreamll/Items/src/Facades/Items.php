<?php

namespace llstarscreamll\Items\Facades;

use Illuminate\Support\Facades\Facade;

class Items extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'items';
    }
}
