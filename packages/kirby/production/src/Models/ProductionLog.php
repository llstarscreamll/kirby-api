<?php

namespace Kirby\Production\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLog extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'machine_id',
        'employee_id',
        'customer_id',
        'batch',
        'tare_weight',
        'gross_weight',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'product_id' => 'int',
        'machine_id' => 'int',
        'employee_id' => 'int',
        'customer_id' => 'int',
        'batch' => 'int',
        'tare_weight' => 'float',
        'gross_weight' => 'float',
    ];
}
