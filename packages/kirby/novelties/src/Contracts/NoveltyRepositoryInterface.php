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
    public function whereScheduledForEmployee(int $employeeId, string $field, Carbon $start, Carbon $end): self;

    public function attachApproversToNovelties(array $approversIds, array $noveltiesIds): bool;

    public function findByEmployeeId(int $employeeId): self;

    public function setApprovals(array $noveltiesIds, int $approverId): void;

    public function deleteApprovals(array $noveltiesIds, int $approverId): void;

    public function deleteApproval(string $noveltyId, string $userId): void;
}
