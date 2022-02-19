<?php

namespace Kirby\TimeClock\Exceptions;

use Exception;

/**
 * Class InvalidNoveltyTypeException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class InvalidNoveltyTypeException extends Exception
{
    /**
     * @var array
     */
    public $timeClockData;

    /**
     * @var string
     */
    protected $message = 'Invalid or missing novelty type.';

    /**
     * @var int
     */
    protected $code = 1055;

    public function __construct(array $timeClockData)
    {
        $this->timeClockData = $timeClockData;
    }
}
