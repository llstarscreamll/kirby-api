<?php

namespace Kirby\Authorization\UI\API\V1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PermissionResource.
 *
 * @property \Kirby\Authorization\Models\Permission $resource
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'guard' => $this->guard,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
