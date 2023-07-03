<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Illuminate\Support\Facades\DB;
use Kirby\TruckScale\TruckScale;
use Kirby\TruckScale\UI\API\V1\Requests\UpdateWeighingSettingsRequest;

class SettingsController
{
    public function index(TruckScale $module)
    {
        return ['data' => $module->rawSettings()];
    }

    public function toggleRequireWeighingMachineLecture(UpdateWeighingSettingsRequest $_)
    {
        DB::statement(<<<'MYSQL'
            UPDATE settings
            SET `value` = IF(value='ON','OFF','ON')
            WHERE `key` = 'truck-scale.require-weighing-machine-lecture'
        MYSQL);

        return ['data' => 'ok'];
    }
}
