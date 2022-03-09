<?php

namespace Kirby\Authorization\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class RoleResource.
 *
 * @property \Kirby\Authorization\Models\Role $resource
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'display_name' => $this->resource->display_name,
            'description' => $this->resource->description,
            'guard' => $this->resource->guard,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
