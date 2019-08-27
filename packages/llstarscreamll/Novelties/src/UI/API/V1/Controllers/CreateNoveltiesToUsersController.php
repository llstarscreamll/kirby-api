<?php

namespace llstarscreamll\Novelties\UI\API\V1\Controllers;

use Illuminate\Support\Facades\DB;
use llstarscreamll\Novelties\Actions\CreateNoveltiesToUsersAction;
use llstarscreamll\Novelties\UI\API\V1\Requests\CreateNoveltiesToUsersRequest;

/**
 * Class CreateNoveltiesToUsersController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesToUsersController
{
    /**
     * @param CreateNoveltiesToUsersRequest $request
     * @param CreateNoveltiesToUsersAction  $action
     */
    public function __invoke(CreateNoveltiesToUsersRequest $request, CreateNoveltiesToUsersAction $action)
    {
        DB::transaction(function () use ($request, $action) {
            $action->run($request->all());
        });

        return response()->json(["ok"], 201);
    }
}
