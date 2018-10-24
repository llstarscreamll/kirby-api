<?php
namespace llstarscreamll\Shippings\Http\Api\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ShippingResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ShippingResource extends JsonResource
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
