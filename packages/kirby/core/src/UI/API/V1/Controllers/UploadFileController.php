<?php

namespace Kirby\Core\UI\API\V1\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Kirby\Core\UI\API\V1\Requests\UploadFileRequest;

/**
 * Class UploadFileController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UploadFileController
{
    public function __invoke(UploadFileRequest $request)
    {
        $path = $request->file('file')->store('files');
 
        return ['data' => Str::afterLast($path, '/')];
    }
}
