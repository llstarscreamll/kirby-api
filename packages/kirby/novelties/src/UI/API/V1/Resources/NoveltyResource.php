<?php

namespace Kirby\Novelties\UI\API\V1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
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
            'employee' => $this->whenLoaded('employee', EmployeeResource::make($this->employee)),
            'novelty_type' => $this->whenLoaded('noveltyType', NoveltyTypeResource::make($this->noveltyType)),
        ] + parent::toArray($request);
    }
}
