<?php

namespace Kirby\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kirby\Customers\Models\Customer;
use Kirby\Employees\Models\Employee;
use Kirby\Machines\Models\Machine;
use Kirby\Products\Models\Product;

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
        'tag',
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
        'tag_updated_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return float
     */
    public function netWeight(): float
    {
        return $this->gross_weight - $this->tare_weight;
    }
}
