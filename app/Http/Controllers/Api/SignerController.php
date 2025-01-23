<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignPdfRequest;
use App\Jobs\PdfSignerJob;

class SignerController extends Controller
{
    public function __invoke(SignPdfRequest $request)
    {
        $pdf = $request->file('pdf')->store('signing');

        $pdf = storage_path('app/'.$pdf);

        PdfSignerJob::dispatch(
            $request->identifier,
            $pdf,
            $request->callback,
            empty($request->employees) ? [] : $request->employees,
            empty($request->signatures) ? [] : array_map(fn ($signature) => array_merge($signature, [
                'certificate' => $signature['certificate']->get(),
                'specimen' => $signature['specimen']->get(),
            ]), $request->signatures),
        );
    }
}
