<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Kirby\Novelties\Novelties;
use Kirby\Novelties\UI\API\V1\Requests\ListNoveltiesSettingsRequest;

/**
 * Class NoveltiesSettingsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesSettingsController
{
    /**
     * @param  ListNoveltiesSettingsRequest  $_
     * @param  Novelties  $novelties
     */
    public function __invoke(ListNoveltiesSettingsRequest $_, Novelties $novelties)
    {
        return response()->json(['data' => $novelties->settings()]);
    }
}
