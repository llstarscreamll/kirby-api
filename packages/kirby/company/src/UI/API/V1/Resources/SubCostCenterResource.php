<?php

namespace Kirby\Company\UI\API\V1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SubCostCenterResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SubCostCenterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
