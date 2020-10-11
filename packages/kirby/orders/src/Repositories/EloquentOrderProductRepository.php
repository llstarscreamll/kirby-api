<?php

namespace Kirby\Orders\Repositories;

use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Orders\Contracts\OrderProductRepository;
use Kirby\Orders\Models\OrderProduct;

/**
 * Class EloquentOrderProductRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentOrderProductRepository extends EloquentRepositoryAbstract implements OrderProductRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return OrderProduct::class;
    }
}