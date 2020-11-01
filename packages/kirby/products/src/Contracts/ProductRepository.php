<?php

namespace Kirby\Products\Contracts;

use Kirby\Core\Contracts\BaseRepositoryInterface;

/**
 * Class ProductRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface ProductRepository extends BaseRepositoryInterface
{
    public function updateByCode(array $data, string $code): int;
}
