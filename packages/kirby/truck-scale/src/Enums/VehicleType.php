<?php

namespace Kirby\TruckScale\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static One()
 * @method static static Two()
 */
final class VehicleType extends Enum
{
    public const One = 'TURBO';
    public const Two = 'SENCILLO';
    public const Three = 'DOBLETROQUE';
    public const Four = 'CUATRO MANOS';
    public const Five = 'MINIMULA - PATINETA';
    public const Six = 'TRACTOMULA DE TRES EJES';
    public const Seven = 'MONTACARGAS';
}
