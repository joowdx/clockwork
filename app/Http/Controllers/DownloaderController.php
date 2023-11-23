<?php

namespace App\Http\Controllers;

use App\Jobs\SynchronizeTimelogs;
use App\Models\Scanner;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DownloaderController extends Controller
{
    /**
     * Download attendance.
     *
     * @param  \App\Contrants\ScannerDriver  $scanner
     * @param  \App\Actions\FileImport\InsertTimelogs  $inserter
     * @return \Illuminate\Http\Response
     */
    public function download(
        Request $request,
        Scanner $scanner,
    ) {
        if (is_null($scanner->ip_address) || empty($scanner->ip_address)) {
            throw ValidationException::withMessages(['message' => 'Scanner is not properly configured.']);
        }

        SynchronizeTimelogs::dispatch($scanner, $request->user(), now());

        return redirect()->back();
    }
}
