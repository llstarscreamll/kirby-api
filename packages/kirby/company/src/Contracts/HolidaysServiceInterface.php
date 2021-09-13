<?php

namespace Kirby\Company\Contracts;

/**
 * Interface HolidaysServiceInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface HolidaysServiceInterface
{
    /**
     * @param  string  $countryCode
     * @param  int  $year
     * @return array
     */
    public function get(string $countryCode, int $year): array;
}
