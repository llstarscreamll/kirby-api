<?php

namespace Kirby\TimeClock\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Setting.
 *
 * @todo move this entity and related stuff to specific package
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['data_type', 'key', 'name', 'description', 'value'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
