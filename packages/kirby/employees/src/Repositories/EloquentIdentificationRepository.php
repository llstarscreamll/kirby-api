<?php

namespace Kirby\Employees\Repositories;

use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Employees\Contracts\IdentificationRepositoryInterface;
use Kirby\Employees\Models\Identification;

/**
 * Class EloquentIdentificationRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentIdentificationRepository extends EloquentRepositoryAbstract implements IdentificationRepositoryInterface
{
    /**
     * @var array
     */
    protected $allowedFilters = ['name'];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return Identification::class;
    }

    /**
     * @return mixed
     */
    public function insert(array $identificationCodes)
    {
        return $this->model->insert($identificationCodes);
    }

    /**
     * @return mixed
     */
    public function deleteWhereEmployeeIdCodesNotIn(int $employeeId, array $codes, string $codeType)
    {
        return $this->model
            ->where(['employee_id' => $employeeId, 'type' => $codeType])
            ->whereNotIn('code', $codes)
            ->delete();
    }

    public function deleteEmployeeUuids(int $employeeId)
    {
        return $this->model
            ->where(['employee_id' => $employeeId, 'type' => 'uuid'])
            ->delete();
    }
}
