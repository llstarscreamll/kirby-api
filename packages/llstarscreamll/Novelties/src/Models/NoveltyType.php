<?php

namespace llstarscreamll\Novelties\Models;

use BenSampo\Enum\Traits\CastsEnums;
use Illuminate\Database\Eloquent\Model;
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
    protected $fillable = ['code', 'name', 'operator'];

    /**
     * The attributes that should be cast to enum types.
     *
     * @var array
     */
    protected $enumCasts = [
        'operator' => NoveltyTypeOperator::class,
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
