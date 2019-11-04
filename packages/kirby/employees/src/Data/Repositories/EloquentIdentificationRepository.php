<?php

namespace Kirby\Employees\Data\Repositories;

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
     * @param array $identificationCodes
     * @return mixed
     */
    public function insert(array $identificationCodes)
    {
        return $this->model->insert($identificationCodes);
    }
}
