<?php

namespace llstarscreamll\TimeClock\Exceptions;

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
     * @param string $message
     */
    public function __construct(string $message = null)
    {
        $this->message = $message ?? $this->message;
    }
}
