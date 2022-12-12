<?php

namespace App\Http\Requests\Employee;

use App\Contracts\Import;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function __construct(
        private Import $import
    ) {
    }

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
            'regular.in' => 'Please select one.',
            'regular.required' => 'Please select one.',
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
            ? [
                'file' => [
                    'required',
                    'file',
                    'mimes:csv,txt',
                    function ($attribute, $file, $fail) {
                        if (! $this->import->validate($file)) {
                            $fail($this->import->error());
                        }
                    },
                ],
            ] : [
                'name.last' => 'required|string',
                'name.first' => 'required|string',
                'name.middle' => 'nullable|string',
                'name.extension' => 'nullable|string',
                'office' => 'nullable|string',
                'regular' => 'required|boolean',
                'active' => 'nullable|boolean',
                'name' => function ($att, $name, $fail) {
                    if (
                        Employee::query()
                            ->where('name->last', strtoupper(@$name['last']) ?? '')
                            ->where('name->first', strtoupper(@$name['first']) ?? '')
                            ->where('name->middle', strtoupper(@$name['middle']) ?? '')
                            ->where('name->extension', strtoupper(@$name['extension']) ?? '')
                            ->exists()
                    ) {
                        $fail('This employee already exists.');
                    }
                },
            ];
    }
}
