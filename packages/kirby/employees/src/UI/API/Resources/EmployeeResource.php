<?php

namespace Kirby\Employees\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class EmployeeResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeeResource extends JsonResource
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
            'id' => $this->id,
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'email' => $this->user->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
