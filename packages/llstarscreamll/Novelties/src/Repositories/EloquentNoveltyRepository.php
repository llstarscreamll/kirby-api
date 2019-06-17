<?php

namespace llstarscreamll\Novelties\Repositories;

use llstarscreamll\Novelties\Models\Novelty;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;

/**
 * Class EloquentNoveltyRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentNoveltyRepository extends EloquentRepositoryAbstract implements NoveltyRepositoryInterface
{
    public function model(): string
    {
        return Novelty::class;
    }
}
