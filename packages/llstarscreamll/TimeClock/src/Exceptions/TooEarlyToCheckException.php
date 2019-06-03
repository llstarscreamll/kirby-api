<?php

namespace llstarscreamll\TimeClock\Exceptions;

use Exception;
use Illuminate\Support\Collection;

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
