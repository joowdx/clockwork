<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PrintRequest extends FormRequest
{
    public function rules(): array
    {
        return match ($this->route('by')) {
            'office' => [
                'dates' => 'required|array',
                'dates.*' => 'required|date:Y-m-d',
                'offices' => 'required_without:groups|array',
                'offices.*' => 'string|distinct|exists:employees,office',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'ulid|exists:scanners,id',
                'groups' => 'required_without:offices|array',
                'groups.*' => 'string|distinct|string',
                'transmittal' => 'nullable|boolean',
            ],
            'employee' => [
                'period' => 'required|in:custom,full,1st,2nd',
                'month' => 'required_if:period,full,1st,2nd|date|date_format:Y-m',
                'from' => 'required_if:period,custom',
                'to' => 'required_if:period,custom',
                'employees' => 'required_without:offices|array',
                'employees.*' => 'ulid|exists:employees,id',
                'offices' => 'required_without:employees|array',
                'offices.*' => 'string|exists:employees,office',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'ulid|exists:scanners,id',
                'regular' => 'sometimes|boolean|exclude_with:employees',
                'transmittal' => 'nullable|boolean',
            ],
            'search' => [
                'name' => 'array|required',
                'name.first' => 'string|required|max:30',
                'name.middle' => 'string|nullable|max:30',
                'name.last' => 'string|required|max:30',
                'name.extension' => 'string|nullable|max:30',
                'period' => 'required|in:custom,full,1st,2nd',
                'month' => 'required_if:period,full,1st,2nd|date|date_format:Y-m',
                'from' => 'required_if:period,custom',
                'to' => 'required_if:period,custom',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'ulid|exists:scanners,id',
                'days' => 'array|nullable',
                'days.*' => 'integer|min:1|max:31|distinct',
                'transmittal' => 'nullable|boolean',
            ],
            default => [
                'period' => 'required|in:custom,full,1st,2nd',
                'month' => 'required_if:period,full,1st,2nd|date|date_format:Y-m',
                'from' => 'required_if:period,custom',
                'to' => 'required_if:period,custom',
                'employees' => 'required_without:offices|array',
                'employees.*' => 'ulid|exists:employees,id',
                'offices' => 'required_without:employees|array',
                'offices.*' => 'string|exists:employees,office',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'ulid|exists:scanners,id',
                'regular' => 'sometimes|boolean|exclude_with:employees',
                'csc_format' => 'sometimes|boolean',
                'calculate' => 'sometimes|boolean',
                'weekdays.excluded' => 'sometimes|boolean',
                'weekdays.am.in' => 'sometimes|nullable|date_format:H:i',
                'weekdays.am.out' => 'sometimes|nullable|string|date_format:H:i',
                'weekdays.pm.in' => 'sometimes|nullable|string|date_format:H:i',
                'weekdays.pm.out' => 'sometimes|nullable|date_format:H:i',
                'weekends.excluded' => 'sometimes|boolean',
                'weekends.am.in' => 'sometimes|nullable|string|date_format:H:i',
                'weekends.am.out' => 'sometimes|nullable|string|date_format:H:i',
                'weekends.pm.in' => 'sometimes|nullable|string|date_format:H:i',
                'weekends.pm.out' => 'sometimes|nullable|string|date_format:H:i',
                'weekends.regular' => 'sometimes|boolean',
                'days' => 'array|nullable',
                'days.*' => 'integer|min:1|max:31|distinct',
                'transmittal' => 'nullable|boolean',
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
                'regular' => filter_var($regular, FILTER_VALIDATE_BOOLEAN),
            ]);
        })->whenFilled('csc_format', function ($csc_format) {
            $this->merge([
                'csc_format' => filter_var($csc_format, FILTER_VALIDATE_BOOLEAN),
            ]);
        })->whenFilled('weekends.regular', function ($regular) {
            $this->merge([
                'weekends.regular' => filter_var($regular, FILTER_VALIDATE_BOOLEAN),
            ]);
        })->whenFilled('weekdays.excluded', function ($exclude) {
            $this->merge([
                'weekdays.excluded' => filter_var($exclude, FILTER_VALIDATE_BOOLEAN),
            ]);
        })->whenFilled('weekends.excluded', function ($exclude) {
            $this->merge([
                'weekends.excluded' => filter_var($exclude, FILTER_VALIDATE_BOOLEAN),
            ]);
        })->whenFilled('calculate', function ($calculate) {
            $this->merge([
                'calculate' => filter_var($calculate, FILTER_VALIDATE_BOOLEAN),
            ]);
        })->whenFilled('transmittal', function ($transmittal) {
            $this->merge([
                'transmittal' => filter_var($transmittal, FILTER_VALIDATE_BOOLEAN),
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
