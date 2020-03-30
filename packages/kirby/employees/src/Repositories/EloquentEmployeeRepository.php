<?php

namespace Kirby\Employees\Repositories;

use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Employees\Contracts\EmployeeRepositoryInterface;
use Kirby\Employees\Models\Employee;

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
     * Fields that are searchable by \Prettus\Repository\Criteria\RequestCriteria.
     *
     * @var array
     */
    protected $fieldSearchable = [
        'user.first_name' => 'like',
        'user.last_name' => 'like',
        'code' => 'like',
        'identification_number' => 'like',
    ];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return Employee::class;
    }
}
