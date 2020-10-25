<?php

namespace Kirby\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use NumberFormatter;

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
        'product_cost' => 'float',
        'product_price' => 'float',
        'product_quantity' => 'float',
        'product_pum_price' => 'float',
        'requested_quantity' => 'int',
    ];

    /**
     * @return float
     */
    public function total(): float
    {
        return $this->requested_quantity * $this->product_price;
    }

    /**
     * @return string
     */
    public function productPriceFormatted(): string
    {
        $formatter = new NumberFormatter('es_CO', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        return $formatter->formatCurrency($this->product_price, 'COP');
    }

    /**
     * @return string
     */
    public function totalFormatted(): string
    {
        $formatter = new NumberFormatter('es_CO', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        return $formatter->formatCurrency($this->total(), 'COP');
    }
}
