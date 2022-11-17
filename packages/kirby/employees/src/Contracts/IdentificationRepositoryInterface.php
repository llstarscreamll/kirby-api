<?php

namespace Kirby\Employees\Contracts;

use Kirby\Core\Contracts\BaseRepositoryInterface;

/**
 * Interface IdentificationRepositoryInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface IdentificationRepositoryInterface extends BaseRepositoryInterface
{
    public function deleteWhereEmployeeIdCodesNotIn(int $employeeId, array $codes, string $codeType);
    public function deleteEmployeeUuids(int $employeeId);
}
