<?php
namespace llstarscreamll\Items\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use llstarscreamll\Items\Models\Tax;

/**
 * Class Item.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Item extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'measure_unit_id',
        'sale_price',
        'purchase_price',
        'tax_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sale_price'      => 'double',
        'purchase_price'  => 'double',
        'measure_unit_id' => 'int',
        'tax_id'          => 'int',
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

    /**
     * @return mixed
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

}
