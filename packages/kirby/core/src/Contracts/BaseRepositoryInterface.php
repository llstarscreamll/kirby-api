<?php

namespace Kirby\Core\Contracts;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface BaseRepositoryInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface BaseRepositoryInterface extends RepositoryInterface
{
    /**
     * Search resources by url query strings on request.
     */
    public function search();

    /**
     * Get allowed filters.
     *
     * @return array
     */
    public function getAllowedFilters();

    public function insert(array $rows);

    public function updateWhereIn(string $field, array $values, array $updates);

    public function deleteWhereNotIn(string $field, array $values): int;

    /**
     * @param $field
     */
    public function deleteWhereIn($field, array $values);
}
