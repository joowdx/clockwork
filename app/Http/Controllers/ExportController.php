<?php

namespace App\Http\Controllers;

use App\Models\Export;

class ExportController extends Controller
{
    public function __invoke(Export $export)
    {
        abort_unless($export->content, 404);

        $export->increment('downloads');

        return response()->streamDownload(function () use ($export) {
            echo $export->content;
        }, $export->filename);
    }
}
