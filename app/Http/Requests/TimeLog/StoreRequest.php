<?php

namespace App\Http\Requests\TimeLog;

use App\Contracts\Import;
use App\Models\Scanner;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function __construct(
        private Import $import
    ) { }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'scanner.required' => 'Please select what scanner to import device logs for.',
            'file.required' => 'Please choose a file.',
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

                    if(!$this->import->validate($this)) {
                        $fail($this->import->error());
                    }
                }
            ],
        ];
    }
}
