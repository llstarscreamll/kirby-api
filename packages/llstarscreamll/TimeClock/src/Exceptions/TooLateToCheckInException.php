<?php

namespace llstarscreamll\TimeClock\Exceptions;

use Exception;

/**
 * Class TooLateToCheckInException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TooLateToCheckInException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'Too late to check in.';

    /**
     * @var int
     */
    protected $code = 1053;

    /**
     * @param string $message
     */
    public function __construct(string $message = null)
    {
        $this->message = $message ?? $this->message;
    }
}
