<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintRequest extends FormRequest
{
    public function rules()
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
                'month' => 'required_if:period,full,1st,2nd',
                'from' => 'required_if:period,custom',
                'to' => 'required_if:period,custom',
                'employees' => 'required|array',
                'employees.*' => 'uuid|exists:employees,id',
                'scanners' => 'sometimes|required|array',
                'scanners.*' => 'uuid|exists:scanners,id',
            ],
        };
    }

    protected function prepareForValidation()
    {
        return $this->merge([
            'offices' => collect($this->offices)->map(fn ($o) => strtoupper($o))->toArray(),
        ]);
    }
}
