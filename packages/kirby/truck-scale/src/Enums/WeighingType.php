<?php

namespace Kirby\TruckScale\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Load()
 * @method static static Unload()
 * @method static static Weighing()
 */
final class WeighingType extends Enum
{
    public const Load = 'load';
    public const Unload = 'unload';
    public const Weighing = 'weighing';
}
