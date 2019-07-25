<?php

namespace llstarscreamll\TimeClock\UI\API\Requests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TimeClockLogApproveRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogApproveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('time-clock-logs.approve');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
