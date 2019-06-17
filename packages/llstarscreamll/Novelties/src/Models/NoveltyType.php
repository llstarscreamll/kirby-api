<?php

namespace llstarscreamll\Novelties\Models;

use BenSampo\Enum\Traits\CastsEnums;
use Illuminate\Database\Eloquent\Model;
use llstarscreamll\Novelties\Enums\DayType;
use Illuminate\Database\Eloquent\SoftDeletes;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;

/**
 * Class NoveltyType.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyType extends Model
{
    use SoftDeletes, CastsEnums;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'context_type',
        'apply_on_days_of_type',
        'apply_on_time_slots',
        'operator',
    ];

    /**
     * The attributes that should be cast to enum types.
     *
     * @var array
     */
    protected $enumCasts = [
        'operator' => NoveltyTypeOperator::class,
        'apply_on_days_of_type' => DayType::class,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'apply_on_time_slots' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
