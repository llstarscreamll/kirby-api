<?php

namespace Kirby\Production\UI\API\V1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Kirby\Employees\UI\API\V1\Resources\EmployeeResource;

/**
 * Class ProductionLogResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ProductionLogResource extends JsonResource
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
            'product_id' => $this->product_id,
            'machine_id' => $this->machine_id,
            'employee_id' => $this->employee_id,
            'customer_id' => $this->customer_id,
            'batch' => $this->batch,
            'tare_weight' => $this->tare_weight,
            'gross_weight' => $this->gross_weight,
            'purpose' => $this->purpose,
            'tag' => $this->tag,
            'tag_updated_at' => $this->tag_updated_at->toIsoString(),
            'created_at' => optional($this->created_at)->toIsoString(),
            'updated_at' => optional($this->updated_at)->toIsoString(),
            'deleted_at' => optional($this->deleted_at)->toIsoString(),

            'employee' => $this->whenLoaded('employee', EmployeeResource::make($this->employee)),
            'product' => $this->whenLoaded('product'),
            'machine' => $this->whenLoaded('machine'),
            'customer' => $this->whenLoaded('customer'),
        ];
    }
}
