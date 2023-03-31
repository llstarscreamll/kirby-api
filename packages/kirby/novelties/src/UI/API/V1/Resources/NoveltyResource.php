<?php

namespace Kirby\Novelties\UI\API\V1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Kirby\Company\UI\API\V1\Resources\SubCostCenterResource;
use Kirby\Employees\UI\API\V1\Resources\EmployeeResource;

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
     * @param  \Illuminate\Http\Request  $request
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
            'start_at' => optional($this->start_at)->toIsoString(),
            'end_at' => optional($this->end_at)->toIsoString(),
            'attachment' => $this->attachment,
            'comment' => $this->comment,
            'created_at' => optional($this->created_at)->toIsoString(),
            'updated_at' => optional($this->updated_at)->toIsoString(),
            'deleted_at' => optional($this->deleted_at)->toIsoString(),

            'approvals' => $this->whenLoaded('approvals'),
            'employee' => $this->whenLoaded('employee', EmployeeResource::make($this->employee)),
            'novelty_type' => $this->whenLoaded('noveltyType', NoveltyTypeResource::make($this->noveltyType)),
            'time_clock_log' => $this->whenLoaded('timeClockLog'),
            'sub_cost_center' => $this->whenLoaded('subCostCenter', SubCostCenterResource::make($this->subCostCenter)),
        ];
    }
}
