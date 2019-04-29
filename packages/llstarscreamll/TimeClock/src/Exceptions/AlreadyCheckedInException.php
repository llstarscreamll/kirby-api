<?php

namespace llstarscreamll\TimeClock\Exceptions;

use Exception;

/**
 * Class AlreadyCheckedInException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class AlreadyCheckedInException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'Already checked in, can\'t check again';

    /**
     * @param string $message
     */
    public function __contruct(string $message = null)
    {
        $this->message = $message ?? $this->message;
    }
}
