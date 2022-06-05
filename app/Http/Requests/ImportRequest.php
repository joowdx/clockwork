<?php

namespace App\Http\Requests;

use App\Models\Scanner;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function messages()
    {
        return [
            'scanner.required' => 'Please select what scanner to import device logs for.',
            'file.required' => 'Please choose a file.',
        ];
    }

    public function rules()
    {
        return [
            'scanner' => [
                'required',
                'exists:scanners,id',
            ],
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                function ($attribute, $value, $fail) {
                    $scanner = Scanner::findOrFail($this->scanner);

                    $user = User::find(auth()->id());

                    if (($attlog = $scanner->attlog_file) && pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME) !== $attlog) {
                        $fail('Please choose the correct file.');
                    }

                    if(!$user->scanners()->find($scanner->id)) {
                        $fail('Not enough privilege.');
                    }
                }
            ],
        ];
    }
}
