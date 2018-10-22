<?php
namespace llstarscreamll\Items\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class MeasureUnit.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class MeasureUnit extends Model
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
        'shipping_from_id',
        'shipment_date',
        'issue_date',
        'status_id',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
