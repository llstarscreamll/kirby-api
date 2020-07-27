<?php

namespace Kirby\Novelties\UI\API\V1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Kirby\Novelties\UI\API\V1\Resources\NoveltyResource;

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
        return [
            'novelties' => NoveltyResource::collection($this->whenLoaded('novelties')),
        ] + parent::toArray($this);
    }
}
