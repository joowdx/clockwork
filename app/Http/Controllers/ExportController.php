<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Export;

class ExportController extends Controller
{
    public function __invoke(Export|string $downloadable)
    {
        if (is_string($downloadable)) {
            $downloadable = Attachment::findOrFail($downloadable);
        }

        abort_unless($downloadable->content, 404);

        if ($downloadable instanceof Export) {
            $downloadable->increment('downloads');
        }

        return response()->streamDownload(function () use ($downloadable) {
            echo $downloadable->content;
        }, pathinfo($downloadable->filename, PATHINFO_BASENAME));
    }
}
