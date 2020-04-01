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
    /**
     * @param int    $employeeId
     * @param string $field
     * @param Carbon $start
     * @param Carbon $end
     */
    public function whereScheduledForEmployee(int $employeeId, string $field, Carbon $start, Carbon $end): self;

    /**
     * @param array $approversIds
     * @param array $noveltiesIds
     */
    public function attachApproversToNovelties(array $approversIds, array $noveltiesIds): bool;

    /**
     * @param int $employeeId
     */
    public function findByEmployeeId(int $employeeId): self;

    /**
     * @param array $noveltiesIds
     * @param int   $approverId
     */
    public function setApprovals(array $noveltiesIds, int $approverId): void;

    /**
     * @param array $noveltiesIds
     * @param int   $approverId
     */
    public function deleteApprovals(array $noveltiesIds, int $approverId): void;

    /**
     * @param string $noveltyId
     * @param string $userId
     */
    public function deleteApproval(string $noveltyId, string $userId): void;
}
