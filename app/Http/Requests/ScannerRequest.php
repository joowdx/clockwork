<?php

namespace App\Http\Requests;

use App\Traits\ConfirmsPassword;
use Illuminate\Foundation\Http\FormRequest;

class ScannerRequest extends FormRequest
{
    use ConfirmsPassword;


    public function messages()
    {
        return [
            'name.unique' => 'This :attribute has already been taken.',
            'attlog_file.unique' => 'This :attribute name has already been taken.',
        ];
    }

    public function rules()
    {
        return $this->isMethod('delete')
            ? [
                'password' => function ($attribute, $password, $fail) {
                    if (! $this->validatePassword($password)) {
                        $fail(__('The password is incorrect.'));
                    }
                },
            ] : [
                'id' => $this->isMethod('post') ? 'nullable' : 'bail|required',
                'name' => [
                    'required',
                    'string',
                    'max:20',
                    $this->isMethod('post') ? 'unique:scanners' : 'unique:scanners,name,' . $this->id
                ],
                'remarks' => 'nullable|string|max:120',
                'attlog_file' => [
                    'nullable',
                    'string',
                    'max:120',
                    $this->isMethod('post') ? 'unique:scanners' : 'unique:scanners,attlog_file,' . $this->id
                ],
                'shared' => 'nullable|boolean',
                'print_text_colour' => 'nullable|color',
                'print_background_colour' => 'nullable|color',
            ];
    }

    protected function prepareForValidation()
    {
        return $this->merge([
            'name' => strtolower($this->name),
            'attlog_file' => strtolower($this->attlog_file),
        ]);
    }
}
