<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PrintRequest extends FormRequest
{
    public function rules(): array
    {
        return match($this->route('by')) {
            'office' => [
                'date' => 'required|date:Y-m-d',
                'offices' => 'required|array',
                'offices.*' => 'string|exists:employees,office',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'uuid|exists:scanners,id',
            ],
            'employee' => [
                'period' => 'required|in:custom,full,1st,2nd',
                'month' => 'required_if:period,full,1st,2nd|date|date_format:Y-m',
                'from' => 'required_if:period,custom',
                'to' => 'required_if:period,custom',
                'employees' => 'required_without:offices|array',
                'employees.*' => 'uuid|exists:employees,id',
                'offices' => 'required_without:employees|array',
                'offices.*' => 'string|exists:employees,office',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'uuid|exists:scanners,id',
                'regular' => 'sometimes|boolean|exclude_with:employees',
            ],
            default => [
                'month' => 'required|date|date_format:Y-m',
                'employees' => 'required_without:offices|array',
                'employees.*' => 'uuid|exists:employees,id',
                'offices' => 'required_without:employees|array',
                'offices.*' => 'string|exists:employees,office',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'uuid|exists:scanners,id',
                'regular' => 'sometimes|boolean|exclude_with:employees',
                'csc_format' => 'sometimes|boolean',
            ],
        };
    }

    protected function prepareForValidation(): mixed
    {
        return $this->whenFilled('offices', function ($offices) {
            $this->merge([
                'offices' => collect($offices)->map(fn ($o) => strtoupper($o))->toArray(),
            ]);
        })->whenFilled('regular', function ($regular) {
            $this->merge([
                'regular' => filter_var($regular, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        })->whenFilled('csc_format', function ($csc_format) {
            $this->merge([
                'csc_format' => filter_var($csc_format, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        });
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
