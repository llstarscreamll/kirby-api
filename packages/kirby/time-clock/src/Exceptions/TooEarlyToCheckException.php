<?php

namespace Kirby\TimeClock\Exceptions;

use Exception;

/**
 * Class TooEarlyToCheckException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TooEarlyToCheckException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'Too early to check.';

    /**
     * @var int
     */
    protected $code = 1054;

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
