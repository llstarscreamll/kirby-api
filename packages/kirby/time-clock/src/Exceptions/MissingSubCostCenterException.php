<?php

namespace Kirby\TimeClock\Exceptions;

use Exception;

/**
 * Class MissingSubCostCenterException.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class MissingSubCostCenterException extends Exception
{
    /**
     * @var array
     */
    public $timeClockData;

    /**
     * @var string
     */
    protected $message = 'Sub cost center is required.';

    /**
     * @var int
     */
    protected $code = 1056;

    public function __construct(array $timeClockData)
    {
        $this->timeClockData = $timeClockData;
    }
}
