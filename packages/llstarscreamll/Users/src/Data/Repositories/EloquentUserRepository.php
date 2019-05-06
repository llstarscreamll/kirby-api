<?php

namespace llstarscreamll\Users\Data\Repositories;

use llstarscreamll\Users\Models\User;
use llstarscreamll\Users\Contracts\UserRepositoryInterface;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;

/**
 * Class EloquentUserRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentUserRepository extends EloquentRepositoryAbstract implements UserRepositoryInterface
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
        return User::class;
    }
}
