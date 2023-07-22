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
            'scanner.required' => 'Please select which scanner to import device logs.',
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
            'scanner' => 'required|exists:scanners,id',
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                function ($attribute, $file, $fail) {
                    $scanner = Scanner::find($this->scanner);

                    $user = User::find(auth()->id());

                    if (($attlog = $scanner?->attlog_file) && pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) !== $attlog) {
                        $fail("Please choose the correct $attribute.");
                    }

                    if (
                        auth()->user()?->administrator
                            ? false
                            : (
                                $scanner?->shared
                                    ? false
                                    : $user->scanners()->doesntExist($scanner->id)
                            )
                    ) {
                        $fail('Not enough privilege.');
                    }

                    // if(! $this->import->validate($file)) {
                    //     $fail($this->import->error());
                    // }
                },
            ],
        ];
    }
}
