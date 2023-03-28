<?php

namespace Kirby\Novelties;

use Illuminate\Support\Collection;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\TimeClock\Contracts\SettingRepositoryInterface;

/**
 * Class Novelties.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Novelties
{
    public function rawSettings(): Collection
    {
        return app(SettingRepositoryInterface::class)
            ->where('key', 'like', 'novelties.%')
            ->get();
    }

    public function settings(): Collection
    {
        $settings = $this->rawSettings();
        $novelties = app(NoveltyTypeRepositoryInterface::class)->findWhereIn('id', $settings->pluck('value')->all());

        return $settings->map(function ($setting) use ($novelties) {
            $setting->value = $novelties->firstWhere('id', $setting->value);

            return $setting;
        });
    }

    public function defaultSubTractNoveltyTypeId(): int
    {
        return $this->rawSettings()->firstWhere('key', 'novelties.default-subtraction-novelty-type')->value;
    }

    public function defaultSubTractNoveltyType(): NoveltyType
    {
        return app(NoveltyTypeRepositoryInterface::class)->find($this->defaultSubTractNoveltyTypeId());
    }

    public function defaultSubTractBalanceNoveltyTypeId(): int
    {
        return $this->rawSettings()->firstWhere('key', 'novelties.default-subtraction-balance-novelty-type')->value;
    }

    public function defaultAdditionBalanceNoveltyTypeId(): int
    {
        return $this->rawSettings()->firstWhere('key', 'novelties.default-addition-balance-novelty-type')->value;
    }
}
