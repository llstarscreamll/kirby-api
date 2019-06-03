<?php

namespace llstarscreamll\TimeClock\Exceptions;

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
     * @param string $message
     */
    public function __construct(string $message = null)
    {
        $this->message = $message ?? $this->message;
    }
}
