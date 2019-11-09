<?php

namespace Kirby\Employees\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'email' => $this->user->email,
            'cost_center_id' => $this->cost_center_id,
            'code' => $this->code,
            'identification_number' => $this->identification_number,
            'position' => $this->position,
            'location' => $this->location,
            'address' => $this->address,
            'phone' => $this->phone,
            'salary' => $this->salary,
            'created_at' => optional($this->created_at)->toIsoString(),
            'updated_at' => optional($this->updated_at)->toIsoString(),
            'deleted_at' => optional($this->updated_at)->toIsoString(),
        ];
    }
}
