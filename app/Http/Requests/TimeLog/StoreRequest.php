<?php

namespace App\Http\Requests\TimeLog;

use App\Contracts\Import;
use App\Models\Scanner;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRequest extends FormRequest
{
    public function __construct(
        private Import $import
    ) {
    }

    // /**
    //  * Get custom messages for validator errors.
    //  *
    //  * @return array
    //  */
    // public function messages()
    // {
    //     return [
    //         'scanner.required' => 'Please select what scanner to import device logs for.',
    //         'file.required' => 'Please choose a file.',
    //     ];
    // }

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
            ],
        ];
    }

    // protected function failedValidation(Validator $validator): never
    // {
    //     throw new HttpResponseException(
    //         response()->json([
    //             'errors' => $validator->errors(),
    //         ], 422)
    //     );
    // }
}
