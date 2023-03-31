<?php

namespace Kirby\Core\UI\API\V1\Controllers;

use Illuminate\Support\Facades\Storage;
use Kirby\Core\UI\API\V1\Requests\DownloadFileRequest;

/**
 * Class DownloadFileController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DownloadFileController
{
    public function __invoke(DownloadFileRequest $request, $fileName)
    {
        $filePath = "files/{$fileName}";

        if (! Storage::exists($filePath)) {
            abort(404);
        }

        return Storage::download($filePath);
    }
}
