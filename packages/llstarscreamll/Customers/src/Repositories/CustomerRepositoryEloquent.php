<?php
namespace llstarscreamll\Customers\Repositories;

use llstarscreamll\Customers\Models\Customer;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class CustomerRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CustomerRepositoryEloquent extends BaseRepository implements CustomerRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'document_number',
        'email',
        'phone',
    ];

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Customer::class;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

}
