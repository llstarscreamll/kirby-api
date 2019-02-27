<?php

namespace llstarscreamll\WorkShifts\Facades;

use Illuminate\Support\Facades\Facade;

class WorkShifts extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'workshifts';
    }
}
