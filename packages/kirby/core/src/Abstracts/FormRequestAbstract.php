<?php

namespace Kirby\Core\Abstracts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

/**
 * Class FormRequestAbstract.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class FormRequestAbstract extends FormRequest
{
    /**
     * Auth user must have ANY of the given roles or permissions.
     *
     * @var array
     */
    protected $access = [
        'roles' => [],
        'permissions' => [],
    ];

    /**
     * @var array
     */
    protected $urlParameters = [];

    /**
     * Override method to make the route params available on request data to be
     * validated.
     *
     * @param  null  $keys
     */
    public function all($keys = null): array
    {
        $requestData = parent::all($keys);

        return $this->mergeUrlParametersToRequestData($requestData);
    }

    /**
     * Check if user has ANY roles OR permissions indicated in $this->access
     * property.
     */
    protected function hasAccess(): bool
    {
        if (isset($this->access) && is_array($this->access)) {
            $permissions = Arr::get($this->access, 'permissions', []);
            $roles = Arr::get($this->access, 'roles', []);

            return $this->user()->hasAnyRole($roles) || $this->user()->hasAnyPermission($permissions);
        }

        return false;
    }

    /**
     * Merge route params into $requestData.
     */
    private function mergeUrlParametersToRequestData(array $requestData): array
    {
        if (isset($this->urlParameters) && ! empty($this->urlParameters)) {
            foreach ($this->urlParameters as $param) {
                $requestData[$param] = $this->route($param);
            }
        }

        return $requestData;
    }
}
