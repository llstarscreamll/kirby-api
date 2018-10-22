<?php
namespace llstarscreamll\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use llstarscreamll\Items\Models\Item;

/**
 * Class Sale.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Sale extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'seller_id',
        'customer_id',
        'shipping_to_id',
        'stockroom_id',
        'status_id',
        'issue_date',
        'shipment_date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'seller_id'        => 'int',
        'customer_id'      => 'int',
        'shipping_to_id'   => 'int',
        'stockroom_id' => 'int',
        'status_id'        => 'int',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'issue_date',
        'shipment_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @return mixed
     */
    public function items()
    {
        return $this->belongsToMany(Item::class)
                    ->withPivot('quantity', 'price', 'tax')
                    ->withTimestamps();
    }
}
