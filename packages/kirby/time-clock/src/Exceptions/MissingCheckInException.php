<?php

namespace Kirby\TimeClock\Exceptions;

use Exception;

/**
 * Class MissingCheckInException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class MissingCheckInException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'Missing check in.';

    /**
     * @var int
     */
    protected $code = 1052;

    /**
     * @param  string  $message
     */
    public function __construct(string $message = null)
    {
        $this->message = $message ?? $this->message;
    }
}
