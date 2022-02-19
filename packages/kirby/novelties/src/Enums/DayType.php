<?php

namespace Kirby\Novelties\Enums;

use BenSampo\Enum\Enum;

/**
 * Class DayType.
 *
 * @method static static Workday()
 * @method static static Holiday()
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
final class DayType extends Enum
{
    public const Workday = 'workday';
    public const Holiday = 'holiday';
}
