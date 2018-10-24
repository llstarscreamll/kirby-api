<?php
namespace llstarscreamll\Shippings\Repositories;

use llstarscreamll\Shippings\Models\Shipping;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class ShippingRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ShippingRepositoryEloquent extends BaseRepository implements ShippingRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
    ];

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Shipping::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

}
