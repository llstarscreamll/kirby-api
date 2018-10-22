<?php
namespace llstarscreamll\Sales\Repositories;

use llstarscreamll\Sales\Models\Sale;
use llstarscreamll\Sales\Repositories\SaleRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class SaleRepositoryEloquent.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SaleRepositoryEloquent extends BaseRepository implements SaleRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Sale::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

}
