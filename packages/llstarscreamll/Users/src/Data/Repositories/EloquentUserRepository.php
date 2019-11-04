<?php

namespace llstarscreamll\Users\Data\Repositories;

use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Users\Contracts\UserRepositoryInterface;
use llstarscreamll\Users\Models\User;

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
