<?php

namespace Kirby\Employees\UI\API\V1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Kirby\Company\UI\API\V1\Resources\CostCenterResource;
use Kirby\WorkShifts\UI\API\V1\Resources\WorkShiftResource;

/**
 * Class EmployeeResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeeResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'cost_center_id' => $this->cost_center_id,
            'code' => $this->code,
            'identification_number' => $this->identification_number,
            'position' => $this->position,
            'location' => $this->location,
            'address' => $this->address,
            'phone' => $this->phone,
            'salary' => $this->salary,
            'cost_center' => new CostCenterResource($this->whenLoaded('costCenter')),
            'work_shifts' => WorkShiftResource::collection($this->whenLoaded('workShifts')),
            'identifications' => IdentificationResource::collection($this->whenLoaded('identifications')),
            'created_at' => optional($this->created_at)->toIsoString(),
            'updated_at' => optional($this->updated_at)->toIsoString(),
            'deleted_at' => optional($this->updated_at)->toIsoString(),
        ];
    }
}
