<?php

namespace Kirby\TimeClock\Exceptions;

use Exception;

/**
 * Class CanNotDeductWorkShiftException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CanNotDeductWorkShiftException extends Exception
{
    /**
     * @var \Illuminate\Support\Collection
     */
    public $timeClockData;

    /**
     * @var string
     */
    protected $message = 'Can not deduct work shift, you must provide which to use.';

    /**
     * @var int
     */
    protected $code = 1051;

    public function __construct(array $timeClockData)
    {
        $this->timeClockData = $timeClockData;
    }
}
