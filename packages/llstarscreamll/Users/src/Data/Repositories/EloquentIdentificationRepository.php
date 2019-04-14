<?php
namespace llstarscreamll\Users\Data\Repositories;

use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Users\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\Users\Models\Identification;

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
}