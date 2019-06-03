<?php

namespace llstarscreamll\TimeClock\Exceptions;

use Exception;
use Illuminate\Support\Collection;

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
    protected $message = 'Too late to check in.';

    /**
     * @var int
     */
    protected $code = 1053;

    /**
     * @var Collection
     */
    public $posibleNoveltyTypes;

    /**
     * @param string $message
     */
    public function __construct(string $message = null, Collection $posibleNoveltyTypes)
    {
        $this->message = $message ?? $this->message;
        $this->posibleNoveltyTypes = $posibleNoveltyTypes;
    }
}
