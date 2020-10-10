<?php

namespace Kirby\Orders\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderProduct.
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class OrderProduct extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'requested_quantity',
        // copy from the original product entity
        'product_code',
        'product_slug',
        'product_sm_image_url',
        'product_md_image_url',
        'product_lg_image_url',
        'product_cost',
        'product_price',
        'product_unity',
        'product_quantity',
        'product_pum_unity',
        'product_pum_price',
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

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'int',
        'product_id' => 'int',
        'product_cost' => 'double',
        'product_price' => 'double',
        'product_quantity' => 'double',
        'product_pum_price' => 'double',
        'requested_quantity' => 'int',
    ];
}
