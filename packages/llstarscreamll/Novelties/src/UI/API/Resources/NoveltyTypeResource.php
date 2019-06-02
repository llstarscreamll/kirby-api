<?php

namespace llstarscreamll\Novelties\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class NoveltyTypeResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyTypeResource extends JsonResource
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
