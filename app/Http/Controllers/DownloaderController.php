<?php

namespace App\Http\Controllers;

use App\Events\TimelogsProcessed;
use App\Models\Scanner;
use App\Services\DownloaderService;
use App\Services\TimelogService;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
        Scanner $scanner,
        TimelogService $service,
        DownloaderService $downloader
    ) {
        if (is_null($scanner->ip_address) || empty($scanner->ip_address)) {
            throw ValidationException::withMessages(['message' => 'Scanner is not properly configured.']);
        }

        try {
            $data = $downloader->getPreformattedAttendance();

            $service->insert($data);

            TimelogsProcessed::dispatch(request()->user(), $data, $scanner);
        } catch (RuntimeException $ex) {
            throw ValidationException::withMessages(['message' => $ex->getMessage()]);
        }

        return redirect()->back();
    }
}
