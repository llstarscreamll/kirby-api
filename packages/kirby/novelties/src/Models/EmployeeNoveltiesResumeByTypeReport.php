<?php

namespace Kirby\Novelties\Models;

use Illuminate\Support\Collection;

class EmployeeNoveltiesResumeByTypeReport
{
    public int $employeeId;
    public string $employeeCode;
    public string $employeeDniNumber;
    public string $employeeFirstName;
    public string $employeeLastName;
    public Collection $noveltiesResume;

    public function __construct(
        string $employeeId,
        string $employeeCode,
        string $employeeDniNumber,
        string $employeeFirstName,
        string $employeeLastName,
        Collection $noveltiesResume
    ) {
        $this->employeeId = $employeeId;
        $this->employeeCode = $employeeCode;
        $this->employeeDniNumber = $employeeDniNumber;
        $this->employeeFirstName = $employeeFirstName;
        $this->employeeLastName = $employeeLastName;
        $this->noveltiesResume = $noveltiesResume;
    }

    public function toCsv(array $sortedAdditionNoveltyTypes, array $sortedSubtractNoveltyTypes): array
    {
        return [
            $this->employeeCode,
            $this->employeeDniNumber,
            "{$this->employeeFirstName} {$this->employeeLastName}",
            ...array_map(
                fn ($noveltyTypeId) => $this->noveltiesResume->where('noveltyTypeId', $noveltyTypeId)->map->elapsedTimeInHours()->sum(),
                $sortedAdditionNoveltyTypes
            ),
            ...array_map(
                fn ($noveltyTypeId) => $this->noveltiesResume->where('noveltyTypeId', $noveltyTypeId)->map->elapsedTimeInHours()->sum(),
                $sortedSubtractNoveltyTypes
            ),
            round($this->noveltiesResume->reduce(fn ($acc, $row) => $acc + $row->elapsedTimeInHours(), 0), 2),
        ];
    }
}
