<?php

namespace Kirby\TruckScale;

use Illuminate\Support\Collection;
use Kirby\TimeClock\Contracts\SettingRepositoryInterface;

class TruckScale
{
    public function rawSettings(): Collection
    {
        return app(SettingRepositoryInterface::class)
            ->where('key', 'like', 'truck-scale.%')
            ->get();
    }
}
