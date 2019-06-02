<?php

namespace llstarscreamll\Novelties\Contracts;

use Illuminate\Support\Collection;
use llstarscreamll\Core\Contracts\BaseRepositoryInterface;

/**
 * Interface NoveltyTypeRepositoryInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface NoveltyTypeRepositoryInterface extends BaseRepositoryInterface
{
    public function findForTimeSubtraction(): Collection;
}
