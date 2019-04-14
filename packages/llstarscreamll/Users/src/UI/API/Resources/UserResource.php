<?php

namespace llstarscreamll\Users\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use llstarscreamll\Authorization\UI\API\Resources\RoleResource;
use llstarscreamll\Authorization\UI\API\Resources\PermissionResource;

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
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'roles'       => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
