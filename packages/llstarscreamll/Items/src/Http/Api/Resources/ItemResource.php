<?php
namespace llstarscreamll\Items\Http\Api\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ItemResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
