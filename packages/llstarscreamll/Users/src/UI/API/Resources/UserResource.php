<?php
namespace llstarscreamll\Users\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class UserResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UserResource extends JsonResource
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
