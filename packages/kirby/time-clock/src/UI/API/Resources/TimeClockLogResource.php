<?php

namespace Kirby\TimeClock\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Kirby\Company\UI\API\Resources\SubCostCenterResource;
use Kirby\Novelties\UI\API\V1\Resources\NoveltyResource;
use Kirby\WorkShifts\UI\API\Resources\WorkShiftResource;

/**
 * Class TimeClockLogResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'sub_cost_center_id' => $this->sub_cost_center_id,
            'work_shift_id' => $this->work_shift_id,
            'checked_in_at' => optional($this->checked_in_at)->toISOString(),
            'check_in_novelty_type_id' => $this->check_in_novelty_type_id,
            'check_in_sub_cost_center_id' => $this->check_in_sub_cost_center_id,
            'checked_out_at' => optional($this->checked_out_at)->toISOString(),
            'check_out_novelty_type_id' => $this->check_out_novelty_type_id,
            'check_out_sub_cost_center_id' => $this->check_out_sub_cost_center_id,
            'checked_in_by_id' => $this->checked_in_by_id,
            'checked_out_by_id' => $this->checked_out_by_id,
            'expected_check_in_at' => optional($this->expected_check_in_at)->toISOString(),
            'expected_check_out_at' => optional($this->expected_check_out_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'deleted_at' => optional($this->deleted_at)->toISOString(),

            'work_shift' => $this->whenLoaded('workShift', WorkShiftResource::make($this->workShift)),
            'employee' => $this->whenLoaded('employee'),
            'check_in_novelty' => $this->whenLoaded('checkInNovelty', NoveltyResource::make($this->checkInNovelty)),
            'check_out_novelty' => $this->whenLoaded('checkOutNovelty', NoveltyResource::make($this->checkOutNovelty)),
            'novelties' => $this->whenLoaded('novelties', NoveltyResource::collection($this->novelties)),
            'sub_cost_center' => $this->whenLoaded('subCostCenter', SubCostCenterResource::make($this->subCostCenter)),
            'check_in_sub_cost_center' => $this->whenLoaded('checkInSubCostCenter', SubCostCenterResource::make($this->checkInSubCostCenter)),
            'check_out_sub_cost_center' => $this->whenLoaded('checkOutSubCostCenter', SubCostCenterResource::make($this->checkOutSubCostCenter)),
            'approvals' => $this->whenLoaded('approvals', NoveltyResource::collection($this->approvals)),
        ];
    }
}
