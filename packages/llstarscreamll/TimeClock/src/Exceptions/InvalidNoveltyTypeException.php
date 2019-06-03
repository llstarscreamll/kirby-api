<?php

namespace llstarscreamll\TimeClock\Exceptions;

use Exception;

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
    protected $message = 'Invalid or missing novelty type.';

    /**
     * @var int
     */
    protected $code = 1055;

    /**
     * @var int
     */
    public $punctuality;

    /**
     * @param int $punctuality
     */
    public function __construct(int $punctuality)
    {
        $this->punctuality = $punctuality;
    }
}
