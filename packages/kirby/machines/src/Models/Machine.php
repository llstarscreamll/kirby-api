<?php

namespace Kirby\Machines\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kirby\Company\Models\SubCostCenter;

class Machine extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'sub_cost_center_id',
        'code',
        'name',
    ];

    public function subCostCenter(): BelongsTo
    {
        return $this->belongsTo(SubCostCenter::class);
    }
}
