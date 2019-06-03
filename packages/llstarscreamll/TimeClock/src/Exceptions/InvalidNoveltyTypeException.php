<?php

namespace llstarscreamll\TimeClock\Exceptions;

use Exception;
use Illuminate\Support\Collection;

/**
 * Class InvalidNoveltyTypeException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class InvalidNoveltyTypeException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'Too late to check.';

    /**
     * @var int
     */
    protected $code = 1055;

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
