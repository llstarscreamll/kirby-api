<?php

namespace Kirby\Authorization\UI\API\V1\Controllers;

use Kirby\Authorization\Models\Role;

class RolesController
{
    public function __invoke()
    {
        return Role::paginate();
    }
}
