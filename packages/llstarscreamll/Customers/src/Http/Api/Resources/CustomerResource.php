<?php
namespace llstarscreamll\Customers\Http\Api\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CustomerResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CustomerResource extends JsonResource
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
