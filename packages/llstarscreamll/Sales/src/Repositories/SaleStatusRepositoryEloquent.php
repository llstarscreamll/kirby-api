<?php
namespace llstarscreamll\Sales\Repositories;

use llstarscreamll\Sales\Models\SaleStatus;
use llstarscreamll\Sales\Repositories\SaleStatusRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class SaleStatusRepositoryEloquent.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SaleStatusRepositoryEloquent extends BaseRepository implements SaleStatusRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SaleStatus::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->findByField('default', true)->first();
    }

}
