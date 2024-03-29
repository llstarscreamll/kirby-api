<?php

namespace Kirby\TimeClock\Repositories;

use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\TimeClock\Contracts\SettingRepositoryInterface;
use Kirby\TimeClock\Models\Setting;

/**
 * Class EloquentSettingRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentSettingRepository extends EloquentRepositoryAbstract implements SettingRepositoryInterface
{
    /**
     * @var array
     */
    protected $allowedFilters = ['key'];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return Setting::class;
    }
}
