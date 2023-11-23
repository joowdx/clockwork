<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function __construct(
        private EmployeeService $import
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
                'groups' => 'nullable|array',
                'groups.*' => 'string|distinct',
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

    protected function prepareForValidation(): void
    {
        $this->whenFilled('groups', function ($groups) {
            $this->merge(['groups' => collect(explode(',', $groups))->filter()->unique()->map(fn ($group) => trim($group))->toArray()]);
        });
    }
}
