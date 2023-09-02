<?php

namespace Kirby\TruckScale\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static InProgress()
 * @method static static Finished()
 */
final class WeighingStatus extends Enum
{
    public const InProgress = 'inProgress';
    public const Finished = 'finished';
    public const ManualFinished = 'manualFinished';
    public const Canceled = 'canceled';
}
