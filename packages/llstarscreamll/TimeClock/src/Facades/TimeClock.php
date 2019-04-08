<?php

namespace llstarscreamll\TimeClock\Facades;

use Illuminate\Support\Facades\Facade;

class TimeClock extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'timeclock';
    }
}
