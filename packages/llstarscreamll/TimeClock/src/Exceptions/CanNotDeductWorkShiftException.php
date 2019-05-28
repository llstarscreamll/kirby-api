<?php
namespace llstarscreamll\TimeClock\Exceptions;

use Exception;

/**
 * Class CanNotDeductWorkShiftException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CanNotDeductWorkShiftException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'Can not deduct work shift, you must provide which to use.';

    /**
     * @var int
     */
    protected $code = 1051;

    /**
     * @param string $message
     */
    public function __construct(string $message = null)
    {
        $this->message = $message ?? $this->message;
    }
}
