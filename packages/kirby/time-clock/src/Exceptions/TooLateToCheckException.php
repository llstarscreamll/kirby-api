<?php

namespace Kirby\TimeClock\Exceptions;

use Exception;

/**
 * Class TooLateToCheckException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TooLateToCheckException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'Too late to check.';

    /**
     * @var int
     */
    protected $code = 1053;

    /**
     * @var array
     */
    public $timeClockData;

    /**
     * @param  array  $timeClockData
     */
    public function __construct(array $timeClockData)
    {
        $this->timeClockData = $timeClockData;
    }
}
