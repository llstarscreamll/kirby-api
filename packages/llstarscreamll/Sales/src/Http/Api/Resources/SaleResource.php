<?php
namespace llstarscreamll\Sales\Http\Api\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'seller_id'   => $this->seller_id,
            'shipping_id' => $this->shipping_id,
            'customer_id' => $this->customer_id,
        ];
    }
}
