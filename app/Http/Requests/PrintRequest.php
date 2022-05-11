<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'month' => ['required', 'date:Y-m'],
            'period' => ['required', 'in:full,1st,2nd'],
            'id' => ['nullable', 'array'],
            'id.*' => ['uuid', 'exists:employees,id']
        ];
    }
}
