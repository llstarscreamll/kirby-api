<?php
namespace llstarscreamll\Stockrooms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use llstarscreamll\Items\Models\Item;

/**
 * Class Stockroom.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Stockroom extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $fillable = [
        'name',
        'address',
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
    public function items()
    {
        return $this->belongsToMany(Item::class)
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}
