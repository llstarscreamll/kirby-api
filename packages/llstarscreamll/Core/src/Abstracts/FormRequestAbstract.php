<?php
namespace llstarscreamll\Core\Abstracts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class FormRequestAbstract.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class FormRequestAbstract extends FormRequest
{
    /**
     * @var array
     */
    protected $urlParameters = [];

    /**
     * Override method to make the route params available on request data to be
     * validated.
     *
     * @param  null    $keys
     * @return array
     */
    public function all($keys = null): array
    {
        $requestData = parent::all($keys);
        $requestData = $this->mergeUrlParametersToRequestData($requestData);

        return $requestData;
    }

    /**
     * Merge route params to the given array.
     *
     * @param  array   $requestData
     * @return array
     */
    private function mergeUrlParametersToRequestData(array $requestData): array
    {
        if (isset($this->urlParameters) && !empty($this->urlParameters)) {
            foreach ($this->urlParameters as $param) {
                $requestData[$param] = $this->route($param);
            }
        }

        return $requestData;
    }
}
