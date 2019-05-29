<?php

namespace llstarscreamll\TimeClock\Exceptions;

use Exception;
use Illuminate\Support\Collection;

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
     * @var \Illuminate\Support\Collection
     */
    public $posibleWorkShifts;

    /**
     * @param string                         $message
     * @param \Illuminate\Support\Collection $posibleWorkShifts
     */
    public function __construct(string $message = null, Collection $posibleWorkShifts)
    {
        $this->message = $message ?? $this->message;
        $this->posibleWorkShifts = $posibleWorkShifts;
    }
}
