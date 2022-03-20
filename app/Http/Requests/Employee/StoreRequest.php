<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.first.required' => 'The first name field is required.',
            'name.last.required' => 'The last name field is required.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return $this->has('file')
            ? ['file' => 'required|file|mimes:csv',]
            : [
                'biometrics_id' => ['required', 'numeric', Rule::unique('employees')->where('user_id', auth()->id())],
                'name.first' => 'required|string',
                'name.last' => 'required|string',
                'name.middle' => 'nullable|string',
                'name.extension' => 'nullable|string',
                'office' => 'nullable|string',
                'regular' => 'required|boolean',
            ];
    }
}
