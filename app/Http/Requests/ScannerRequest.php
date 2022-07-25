<?php

namespace App\Http\Requests;

use App\Traits\ConfirmsPassword;
use Illuminate\Foundation\Http\FormRequest;

class ScannerRequest extends FormRequest
{
    use ConfirmsPassword;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
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
                'name' => 'required|string',
                'remarks' => 'nullable|string',
                'attlog_file' => 'nullable|string',
                'shared' => 'required|boolean',
                'print_text_colour' => 'nullable|color',
                'print_background_colour' => 'nullable|color',
            ];
    }
}
