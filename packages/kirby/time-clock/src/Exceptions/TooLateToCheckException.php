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
     * @var array
     */
    public $timeClockData;

    /**
     * @var string
     */
    protected $message = 'Too late to check.';

    /**
     * @var int
     */
    protected $code = 1053;

    public function __construct(array $timeClockData)
    {
        $this->timeClockData = $timeClockData;
    }
}
