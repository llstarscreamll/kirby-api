<?php

namespace Kirby\Novelties\Contracts;

use Kirby\Novelties\DTOs\SearchEmployeeNoveltiesData;

/**
 * Interface NoveltyReportingRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
interface NoveltyReportingRepository
{
    public function employeesResumeByNoveltyTypeChunk(SearchEmployeeNoveltiesData $data, int $chunkSize = 1000, callable $callback);
}
