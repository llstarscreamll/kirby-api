<?php

namespace Kirby\Users\Repositories;

use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Users\Contracts\UserRepositoryInterface;
use Kirby\Users\Models\User;

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
