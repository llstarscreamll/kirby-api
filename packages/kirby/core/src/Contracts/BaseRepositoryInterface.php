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

    /**
     * @param array $rows
     */
    public function insert(array $rows);

    /**
     * @param string $field
     * @param array  $values
     * @param array  $updates
     */
    public function updateWhereIn(string $field, array $values, array $updates);

    /**
     * @param  string $field
     * @param  array  $values
     * @return int
     */
    public function deleteWhereNotIn(string $field, array $values): int;

    /**
     * @param $field
     * @param array    $values
     */
    public function deleteWhereIn($field, array $values);
}
