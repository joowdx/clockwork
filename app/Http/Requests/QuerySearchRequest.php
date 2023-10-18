<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuerySearchRequest extends FormRequest
{
    public function attributes(): array
    {
        return [
            'name.first' => 'first name',
            'name.middle' => 'middle name',
            'name.last' => 'last name',
            'name.middle' => 'middle name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.first.required' => 'Your :attribute is required.',
            'name.last.required' => 'Your :attribute is required.',
        ];
    }

    public function rules(): array
    {
        return match (strtolower($this->method())) {
            'post' => [
                'name.first' => 'required|string',
                'name.last' => 'required|string',
                'name.middle' => 'nullable|string',
                'name.extension' => 'nullable|string',
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        $name = [];

        if (isset($this->name['first'])) {
            $name['first'] = mb_strtoupper($this->name['first']);
        }

        if (isset($this->name['middle'])) {
            $name['middle'] = mb_strtoupper($this->name['middle']);
        }

        if (isset($this->name['last'])) {
            $name['last'] = mb_strtoupper($this->name['last']);
        }

        if (isset($this->name['extension'])) {
            $name['extension'] = mb_strtoupper($this->name['extension']);
        }

        $this->merge(['name' => $name]);
    }
}
