<?php

namespace Kirby\Novelties;

use Illuminate\Support\Collection;
use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\TimeClock\Contracts\SettingRepositoryInterface;

/**
 * Class Novelties
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Novelties
{
    /**
     * @return Collection
     */
    public function rawSettings(): Collection
    {
        return app(SettingRepositoryInterface::class)
            ->where('key', 'like', 'novelties.%')
            ->get();
    }

    /**
     * @return Collection
     */
    public function settings(): Collection
    {
        $settings = $this->rawSettings();
        $novelties = app(NoveltyTypeRepositoryInterface::class)->findWhereIn('id', $settings->pluck('value')->all());

        return $settings->map(function ($setting) use ($novelties) {
            $setting->value = $novelties->firstWhere('id', $setting->value);

            return $setting;
        });
    }
}
