<?php

namespace Kirby\Novelties\Enums;

use BenSampo\Enum\Enum;

/**
 * Class NoveltyTypeOperator.
 *
 * @method static static None()
 * @method static static Addition()
 * @method static static Subtraction()
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
final class NoveltyTypeOperator extends Enum
{
    const None = 'none';
    const Addition = 'addition';
    const Subtraction = 'subtraction';
}
