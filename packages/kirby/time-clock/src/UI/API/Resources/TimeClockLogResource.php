<?php

namespace Kirby\TimeClock\UI\API\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class TimeClockLogResource.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogResource extends JsonResource
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
