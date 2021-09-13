<?php

namespace Kirby\Novelties\Contracts;

use Illuminate\Support\Collection;
use Kirby\Core\Contracts\BaseRepositoryInterface;

/**
 * Interface NoveltyTypeRepositoryInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface NoveltyTypeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param  array  $columns
     */
    public function findForTimeSubtraction($columns = ['*']): Collection;

    /**
     * @param  array  $columns
     */
    public function findForTimeAddition($columns = ['*']): Collection;
}
