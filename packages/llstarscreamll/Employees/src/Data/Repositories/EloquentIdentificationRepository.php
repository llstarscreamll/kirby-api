<?php

namespace llstarscreamll\Employees\Data\Repositories;

use llstarscreamll\Employees\Models\Identification;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;

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
     * @param array $identificationCodes
     * @return mixed
     */
    public function insert(array $identificationCodes)
    {
        return $this->model->insert($identificationCodes);
    }
}
