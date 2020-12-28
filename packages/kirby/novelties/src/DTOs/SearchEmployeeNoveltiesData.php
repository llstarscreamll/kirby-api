<?php

namespace Kirby\Novelties\DTOs;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\DataTransferObject;

class SearchEmployeeNoveltiesData extends DataTransferObject
{
    public int $userId;

    public ?int $employeeId;

    public Carbon $startDate;

    public Carbon $endDate;

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self([
            'userId' => $request->user()->id,
            'employeeId' => $data['employee_id'] ?? null,
            'startDate' => Carbon::parse($data['start_at']),
            'endDate' => Carbon::parse($data['end_at']),
        ]);
    }
}
