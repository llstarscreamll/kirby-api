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
     * @var string
     */
    protected $message = 'Sub cost center is required.';

    /**
     * @var int
     */
    protected $code = 1056;

    /**
     * @var array
     */
    public $timeClockData;

    /**
     * @param array $timeClockData
     */
    public function __construct(array $timeClockData)
    {
        $this->timeClockData = $timeClockData;
    }
}
