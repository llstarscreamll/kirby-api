<?php
namespace llstarscreamll\Stockrooms\Http\Api\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class StockroomResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class StockroomResource extends JsonResource
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
