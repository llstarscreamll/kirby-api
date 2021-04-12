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
        'name',
        'diameter_in_mm',
    ];

}
