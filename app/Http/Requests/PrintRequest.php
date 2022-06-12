<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PrintRequest extends FormRequest
{
    public function rules()
    {
        return [
            'view' => [
                'required',
                'in:employee,office',
            ],
            'by' => [
                'required_if:view,employee',
                'in:period,range',
            ],
            'from' => [
                'required_if:by,range',
                'required_with:to',
                'date:Y-m-d',
            ],
            'to' => [
                'required_if:by,range',
                'required_with:from',
                'date:Y-m-d',
            ],
            'month' => [
                'required_if:by,period',
                'required_with:period',
                'date:Y-m',
            ],
            'period' => [
                'required_if:by,period',
                'required_with:month',
                'in:full,1st,2nd',
            ],
            'date' => [
                'required_if:view,office',
                'date:Y-m-d',
            ],
            'offices' => [
                'nullable',
                'array',
            ],
            'offices.*' => [
                'sometimes',
                'exists:employees,office',
            ],
            'employees' => [
                'required_if:view,employee',
                'array',
            ],
            'employees.*' => [
                'sometimes',
                'exists:employees,id',
            ]
        ];
    }

    protected function prepareForValidation()
    {
        return $this->merge([
            'offices' => collect($this->offices)->map(fn ($o) => strtoupper($o))->toArray(),
        ]);
    }
}
