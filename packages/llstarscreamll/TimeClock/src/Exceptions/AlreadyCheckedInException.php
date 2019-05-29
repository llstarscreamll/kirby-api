<?php

namespace llstarscreamll\TimeClock\Exceptions;

use Exception;
use Carbon\Carbon;

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
     * @var int
     */
    protected $code = 1050;

    /**
     * @var \Carbon\Carbon
     */
    public $checkedInAt;

    /**
     * @param string $message
     * @param Carbon $checkedInAt
     */
    public function __construct(string $message = null, Carbon $checkedInAt)
    {
        $this->message = $message ?? $this->message;
        $this->checkedInAt = $checkedInAt;
    }
}
