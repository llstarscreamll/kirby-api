<?php

namespace Kirby\Production\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Sales()
 * @method static static Consumption()
 */
final class Purpose extends Enum
{
    public const Sales = 'Sales';
    public const Consumption = 'Consumption';
}
