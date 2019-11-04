<?php

namespace llstarscreamll\TimeClock\Data\Repositories;

use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\TimeClock\Contracts\SettingRepositoryInterface;
use llstarscreamll\TimeClock\Models\Setting;

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

    /**
     * @return string
     */
    public function model(): string
    {
        return Setting::class;
    }
}
