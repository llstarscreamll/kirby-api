<?php

namespace Kirby\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Kirby\Users\Models\User;
use NumberFormatter;

/**
 * Class Order.
 *
 * @property int $id
 * @property int  user_id
 * @property string payment_method
 * @property string address
 * @property string address_additional_info
 * @property float shipping_price
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'address',
        'address_additional_info',
        'shipping_price',
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
        'user_id' => 'int',
        'shipping_price' => 'float',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(OrderProduct::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return float
     */
    public function shippingPriceFormatted(): string
    {
        $formatter = new NumberFormatter('es_CO', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        return $formatter->formatCurrency($this->shipping_price, 'COP');
    }

    /**
     * @return float
     */
    public function total(): float
    {
        return $this->products->sum(fn ($product) => $product->total()) + $this->shipping_price;
    }

    /**
     * @return float
     */
    public function totalFormatted(): string
    {
        $formatter = new NumberFormatter('es_CO', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        return $formatter->formatCurrency($this->total(), 'COP');
    }
}
