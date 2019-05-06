<?php

namespace llstarscreamll\Employees\Data\Repositories;

use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Employees\Contracts\EmployeeRepositoryInterface;

/**
 * Class EloquentEmployeeRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentEmployeeRepository extends EloquentRepositoryAbstract implements EmployeeRepositoryInterface
{
    /**
     * @var array
     */
    protected $allowedFilters = ['code', 'identification_number'];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return Employee::class;
    }
}
