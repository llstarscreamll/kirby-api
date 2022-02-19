<?php

namespace Kirby\Company\Contracts;

/**
 * Interface HolidaysServiceInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface HolidaysServiceInterface
{
    public function get(string $countryCode, int $year): array;
}
