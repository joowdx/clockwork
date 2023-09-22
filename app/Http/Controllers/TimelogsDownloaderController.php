<?php

namespace App\Http\Controllers;

use App\Contracts\ScannerDriver;
use App\Events\TimelogsProcessed;
use App\Models\Scanner;
use App\Services\TimelogService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;

class TimelogsDownloaderController extends Controller
{
    /**
     * Download attlogs.
     *
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
            $data = $driver->getFormattedAttlogs($scanner->id);

            $service->insert($data);

            TimelogsProcessed::dispatch(request()->user(), $data, $scanner, true);
        } catch (ConnectionException  $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        return redirect()->back();
    }
}
