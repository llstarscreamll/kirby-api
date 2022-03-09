<?php

namespace Kirby\TimeClock\Exceptions;

use Carbon\Carbon;
use Exception;

/**
 * Class AlreadyCheckedInException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class AlreadyCheckedInException extends Exception
{
    /**
     * @var \Carbon\Carbon
     */
    public $checkedInAt;

    /**
     * @var string
     */
    protected $message = 'Already checked in, can\'t check again.';

    /**
     * @var int
     */
    protected $code = 1050;

    /**
     * @param  string  $message
     */
    public function __construct(Carbon $checkedInAt)
    {
        $this->checkedInAt = $checkedInAt;
    }
}
