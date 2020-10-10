<?php

namespace Kirby\Products\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Category.
 *
 * @property string $name
 * @property string $slug
 * @property string $image_url
 * @property int $position
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Category extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug', 'image_url', 'position', 'active'];

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
        'position' => 'int',
        'active' => 'bool',
    ];

    # ######################################################################## #
    # Relations
    # ######################################################################## #

    /**
     * @return mixed
     */
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * @return mixed
     */
    public function firstTenProducts()
    {
        return $this->products()->limit(10);
    }
}
