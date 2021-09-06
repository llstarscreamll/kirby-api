<?php

namespace Kirby\Machines\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kirby\Company\Models\CostCenter;

class Machine extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'cost_center_id',
        'code',
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
