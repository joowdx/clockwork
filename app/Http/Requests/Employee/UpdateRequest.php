<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.first.required' => 'First name is required.',
            'name.last.required' => 'Last name is required.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'biometrics_id' => ['nullable', 'numeric', Rule::unique('employees')->ignore(@$this->id[0])->where('user_id', auth()->id())],
            'name.first' => 'required|string',
            'name.last' => 'required|string',
            'name.middle' => 'nullable|string',
            'name.extension' => 'nullable|string',
            'office' => 'nullable|string',
            'regular' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ];
    }
}
