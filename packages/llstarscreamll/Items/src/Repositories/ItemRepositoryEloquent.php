<?php
namespace llstarscreamll\Items\Repositories;

use llstarscreamll\Items\Models\Item;
use llstarscreamll\Items\Repositories\ItemRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class ItemRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ItemRepositoryEloquent extends BaseRepository implements ItemRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'description',
    ];

    /**
     * Specify Model class name.
     *
     * @return string
     */
    public function model()
    {
        return Item::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

}
