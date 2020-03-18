<?php

namespace Kirby\Novelties\UI\API\V1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Kirby\Company\UI\API\V1\Resources\SubCostCenterResource;
use Kirby\Employees\UI\API\Resources\EmployeeResource;

/**
 * Class NoveltyResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'time_clock_log_id' => $this->time_clock_log_id,
            'employee_id' => $this->employee_id,
            'novelty_type_id' => $this->novelty_type_id,
            'sub_cost_center_id' => $this->sub_cost_center_id,
            'scheduled_start_at' => optional($this->scheduled_start_at)->toISOString(),
            'scheduled_end_at' => optional($this->scheduled_end_at)->toISOString(),
            'total_time_in_minutes' => $this->total_time_in_minutes,
            'comment' => $this->comment,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'deleted_at' => optional($this->deleted_at)->toISOString(),

            'employee' => $this->whenLoaded('employee', EmployeeResource::make($this->employee)),
            'novelty_type' => $this->whenLoaded('noveltyType', NoveltyTypeResource::make($this->noveltyType)),
            'time_clock_log' => $this->whenLoaded('timeClockLog'),
            'sub_cost_center' => $this->whenLoaded('subCostCenter', SubCostCenterResource::make($this->subCostCenter)),
        ];
    }
}
