<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PdfSignerJob;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use LSNepomuceno\LaravelA1PdfSign\Exceptions\ProcessRunTimeException;
use LSNepomuceno\LaravelA1PdfSign\Sign\ManageCert;

class SignerController extends Controller
{
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'callback' => 'nullable|url|active_url',
            'pdf' => 'required|file|mimes:pdf|max:10mb',
            'employees' => 'nullable|array',
            'employees.*.field' => 'required|string',
            'employees.*.page' => 'required|integer|min:1',
            'employees.*.coordinates' => 'required|string|regex:/^(\d+,\s*\d+,\s*\d+,\s*\d+)$/',
            'employees.*.reason' => 'nullable|string|max:255',
            'employees.*.location' => 'nullable|string|max:255',
            'employees.*.uid' => [
                'required',
                'string',
                'size:8',
                'exists:employees,uid',
                function ($attribute, $value, $fail) {
                    $employee = Employee::where('uid', $value)->first();

                    if ($employee === null) {
                        return;
                    }

                    if (! $employee->signature?->specimen || ! $employee->signature?->certificate || ! $employee->signature?->password) {
                        return $fail('The employee is not configured for signing.');
                    }
                },
            ],
            'signatures' => 'nullable|array',
            'signatures.*.field' => 'required|string',
            'signatures.*.page' => 'required|integer|min:1',
            'signatures.*.coordinates' => 'required|string|regex:/^(\d+,\s*\d+,\s*\d+,\s*\d+)$/',
            'signatures.*.reason' => 'nullable|string|max:255',
            'signatures.*.location' => 'nullable|string|max:255',
            'signatures.*.contact' => 'nullable|string|max:255',
            'signatures.*.specimen' => 'required|file|mimes:jpg,png|max:10240',
            'signatures.*.certificate.*' => 'required|file|mimetypes:application/x-pkcs12|max:128',
            'signatures.*.password' => [
                'required',
                'string',
                function ($attribute, $value, $fail, $validator) {
                    $index = explode('.', $attribute)[1];

                    $certificate = @$validator->getData()['signatures'][$index]['certificate'];

                    if ($certificate === null) {
                        return;
                    }

                    try {
                        (new ManageCert)->fromUpload($certificate, $value);

                        return;
                    } catch (ProcessRunTimeException $exception) {
                        if (str($exception->getMessage())->contains('password')) {
                            return $fail('The password is incorrect.');
                        }

                        throw $exception;
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $pdf = $request->file('pdf')->store('signing');

        $pdf = storage_path('app/'.$pdf);

        PdfSignerJob::dispatch(
            $pdf,
            $request->callback,
            $request->employees,
            array_map(fn ($signature) => array_merge($signature, [
                'certificate' => $signature['certificate']->get(),
                'specimen' => $signature['specimen']->get(),
            ]), $request->signatures),
        );
    }
}
