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
                'regular' => 'nullable|boolean',
            ],
            default => [
                'month' => 'required|date|date_format:Y-m',
                'employees' => 'required_without:offices|array',
                'employees.*' => 'uuid|exists:employees,id',
                'offices' => 'required_without:employees|array',
                'offices.*' => 'string|exists:employees,office',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'uuid|exists:scanners,id',
                'regular' => 'nullable|boolean',
            ],
        };
    }

    protected function prepareForValidation(): mixed
    {
        return $this->filled('offices') ? $this->merge([
            'offices' => collect($this->offices)->map(fn ($o) => strtoupper($o))->toArray(),
        ]) : [];
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
