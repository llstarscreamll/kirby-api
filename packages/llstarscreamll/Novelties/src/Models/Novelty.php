<?php

namespace llstarscreamll\Novelties\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Novelty.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Novelty extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'time_clock_log_id',
        'novelty_type_id',
        'employee_id',
        'total_time_in_minutes',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    // ######################################################################## #
    //                                 Relations                                #
    // ######################################################################## #

    /**
     * @return mixed
     */
    public function noveltyType()
    {
        return $this->belongsTo(NoveltyType::class);
    }
}
