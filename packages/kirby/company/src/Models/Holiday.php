<?php

namespace Kirby\Company\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Holiday.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Holiday extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_code',
        'name',
        'description',
        'date',
    ];

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
