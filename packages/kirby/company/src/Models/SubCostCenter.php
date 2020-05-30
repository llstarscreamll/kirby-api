<?php

namespace Kirby\Company\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SubCostCenter.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SubCostCenter extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cost_center_id', 'code', 'name',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @return mixed
     */
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
