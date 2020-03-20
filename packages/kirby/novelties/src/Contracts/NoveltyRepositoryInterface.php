<?php

namespace Kirby\Novelties\Contracts;

use Carbon\Carbon;
use Kirby\Core\Contracts\BaseRepositoryInterface;

/**
 * Interface NoveltyRepositoryInterface.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface NoveltyRepositoryInterface extends BaseRepositoryInterface
{

    public function whereScheduledForEmployee(int $employeeId, string $field, Carbon $start, Carbon $end);

    public function attachApproversToNovelties(array $approversIds, array $noveltiesIds): bool;
}
