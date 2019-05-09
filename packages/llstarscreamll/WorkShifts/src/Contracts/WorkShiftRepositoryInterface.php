<?php

namespace llstarscreamll\WorkShifts\Contracts;

use llstarscreamll\Core\Contracts\BaseRepositoryInterface;

/**
 * Interface WorkShiftRepositoryInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface WorkShiftRepositoryInterface extends BaseRepositoryInterface
{
    public function deleteWhereNotIn(string $field, array $values): int;
}
