<?php

namespace Kirby\Production\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static InLine()
 * @method static static Error()
 * @method static static Rejected()
 */
final class Tag extends Enum
{
    const InLine = 'InLine';
    const Error = 'Error';
    const Rejected = 'Rejected';
}
