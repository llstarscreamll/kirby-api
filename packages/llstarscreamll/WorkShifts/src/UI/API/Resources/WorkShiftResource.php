<?php
namespace llstarscreamll\WorkShifts\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class WorkShiftResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($this);
    }
}
