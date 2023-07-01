<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\TruckScale\TruckScale;

class SettingsController
{
    public function index(TruckScale $module)
    {
        return ['data' => $module->rawSettings()];
    }
}
