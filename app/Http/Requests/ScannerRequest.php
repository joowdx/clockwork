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
                'timelogs' => ['nullable', 'boolean'],
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
                    $this->isMethod('post') ? 'unique:scanners' : 'unique:scanners,name,'.$this->id,
                ],
                'remarks' => 'nullable|string|max:120',
                'attlog_file' => [
                    'nullable',
                    'string',
                    'max:120',
                    $this->isMethod('post') ? 'unique:scanners' : 'unique:scanners,attlog_file,'.$this->id,
                ],
                'shared' => 'nullable|boolean',
                'print_text_colour' => 'required|color',
                'print_background_colour' => 'required|color',
                'driver' => 'required_with:ip_address|nullable|string|in:zakzk,tadphp',
                'ip_address' => 'required_with:driver,port|nullable|ip',
                'port' => 'nullable|integer|min:0',
            ];
    }
}
