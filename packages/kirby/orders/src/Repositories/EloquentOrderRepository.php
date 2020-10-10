<?php

namespace Kirby\Orders\Repositories;

use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Orders\Contracts\OrderRepository;
use Kirby\Orders\Models\Order;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class EloquentOrderRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentOrderRepository extends EloquentRepositoryAbstract implements OrderRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Order::class;
    }

    /**
     * @param $limit
     * @param array     $columns
     * @param $method
     */
    public function paginate($limit = null, $columns = ['*'], $method = 'paginate')
    {
        return QueryBuilder::for($this->model())
            ->defaultSort('-id')
            ->paginate($limit, $columns);
    }
}
