<?php
namespace llstarscreamll\TimeClock\Exceptions;

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
    protected $message = 'Missing check in';
}
