<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintRequest extends FormRequest
{
    public function rules()
    {
        return [
            'view' => [
                'required',
                'in:employee,office',
            ],
            'period' => [
                'required_if:view,employee',
                'in:custom,full,1st,2nd,',
            ],
            'from' => [
                'required_if:period,custom',
                'required_with:to',
                'date:Y-m-d',
            ],
            'to' => [
                'required_if:period,custom',
                'required_with:from',
                'date:Y-m-d',
            ],
            'month' => [
                'required_if:period,full',
                'required_if:period,1st',
                'required_if:period,2nd',
                'date:Y-m',
            ],
            'date' => [
                'required_if:view,office',
                'date:Y-m-d',
            ],
            'offices' => [
                'required_if:view,office',
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
