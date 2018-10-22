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
            'id'             => $this->id,
            'seller_id'      => $this->seller_id,
            'customer_id'    => $this->customer_id,
            'shipping_to_id' => $this->shipping_to_id,
            'stockroom_id'   => $this->stockroom_id,
            'status_id'      => $this->status_id,
            'issue_date'     => $this->issue_date,
            'shipment_date'  => $this->shipment_date,
        ];
    }
}
