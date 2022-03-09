<?php

namespace Kirby\Novelties\Models;

use Carbon\Carbon;
use Kirby\Novelties\Enums\NoveltyTypeOperator;

class NoveltyResume
{
    public string $noveltyTypeId;
    public string $noveltyTypeOperator;
    public Carbon $startAt;
    public Carbon $endAt;

    public function __construct(string $noveltyTypeId, string $noveltyTypeOperator, string $startAt, string $endAt)
    {
        $this->noveltyTypeId = $noveltyTypeId;
        $this->noveltyTypeOperator = $noveltyTypeOperator;
        $this->startAt = Carbon::parse($startAt);
        $this->endAt = Carbon::parse($endAt);
    }

    public function elapsedTimeInHours(): float
    {
        return round($this->startAt->diffInSeconds($this->endAt) / 60 / 60, 2) * (NoveltyTypeOperator::Subtraction === $this->noveltyTypeOperator ? -1 : 1);
    }
}
