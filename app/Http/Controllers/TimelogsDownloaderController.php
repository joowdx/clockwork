<?php

namespace App\Http\Controllers;

use App\Contracts\ScannerDriver;
use App\Models\Scanner;
use App\Services\TimelogService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;

class TimelogsDownloaderController extends Controller
{
    /**
     * Download attlogs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Contrants\ScannerDriver  $scanner
     * @param  \App\Actions\FileImport\InsertTimelogs  $inserter
     * @return \Illuminate\Http\Response
     */
    public function download(Scanner $scanner, TimelogService $service, ?ScannerDriver $driver)
    {
        if ($driver === null) {
            throw ValidationException::withMessages(['message' => 'Scanner is not configured.']);
        }

        try {
            $service->insert($driver->getFormattedAttlogs($scanner->id));
        } catch (ConnectionException  $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        return redirect()->back();
    }
}
