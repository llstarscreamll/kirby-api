<?php

namespace Kirby\Products\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'internal_code',
        'customer_code',
        'short_name',
        'name',
        'wire_gauge_in_bwg',
        'wire_gauge_in_mm',
    ];
}
