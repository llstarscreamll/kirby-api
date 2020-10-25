<?php

namespace Kirby\Products\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Product.
 *
 * @property string $name
 * @property string $slug
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Product extends Model
{
    use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'code',
        'slug',
        'sm_image_url', 'md_image_url', 'lg_image_url',
        'cost', 'price', 'unity', 'quantity',
        'pum_unity', 'pum_price',
        'active',
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
        'active' => 'bool',
    ];

    /**
     * @return mixed
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
