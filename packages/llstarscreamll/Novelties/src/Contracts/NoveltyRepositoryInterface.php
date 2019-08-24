<?php

namespace llstarscreamll\Novelties\Contracts;

use Carbon\Carbon;
use llstarscreamll\Core\Contracts\BaseRepositoryInterface;

/**
 * Interface NoveltyRepositoryInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface NoveltyRepositoryInterface extends BaseRepositoryInterface
{
    public function whereScheduledForEmployee($employeeId, string $field, Carbon $start, Carbon $end);
}
