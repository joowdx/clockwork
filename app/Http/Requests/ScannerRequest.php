<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScannerRequest extends FormRequest
{
    // /**
    //  * Determine if the user is authorized to make this request.
    //  *
    //  * @return bool
    //  */
    // public function authorize()
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string',
            'remarks' => 'nullable|string',
            'attlog_file' => 'nullable|string',
            'shared' => 'required|boolean',
            'print_text_colour' => 'nullable|color',
            'print_background_colour' => 'nullable|color',
        ];
    }
}
