<?php
namespace llstarscreamll\Stockrooms\Repositories;

use llstarscreamll\Stockrooms\Models\Stockroom;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class StockroomRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class StockroomRepositoryEloquent extends BaseRepository implements StockroomRepository
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
        return Stockroom::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

}
