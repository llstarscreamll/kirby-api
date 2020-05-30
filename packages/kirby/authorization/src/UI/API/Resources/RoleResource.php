<?php

namespace Kirby\Authorization\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class RoleResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RoleResource extends JsonResource
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
            'id'           => $this->id,
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'description'  => $this->description,
            'guard'        => $this->guard,
            'permissions'  => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
