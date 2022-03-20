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
            'name.first' => 'nullable|string',
            'name.last' => 'nullable|string',
            'name.middle' => 'nullable|string',
            'name.extension' => 'nullable|string',
            'office' => 'nullable|string',
            'regular' => 'nullable|in:1,0,*',
            'active' => 'nullable|in:1,0,*',
        ];
    }
}
