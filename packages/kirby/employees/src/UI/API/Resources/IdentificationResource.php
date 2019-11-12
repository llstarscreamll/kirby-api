<?php

namespace Kirby\Employees\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class IdentificationResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class IdentificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($this);
    }
}
