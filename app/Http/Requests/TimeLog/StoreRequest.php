<?php

namespace App\Http\Requests\TimeLog;

use App\Contracts\Import;
use App\Models\Scanner;
use App\Models\Timelog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function __construct(
        private Import $import
    ) {
    }

    public function messages()
    {
        return [
            'scanner.required' => 'Please select which scanner to import device logs.',
            'file.required' => 'Please choose a file.',
            'time.unique' => 'Select another time.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->has('file')) {
            return [
                'scanner' => 'required|exists:scanners,id',
                'file' => [
                    'required',
                    'file',
                    'mimes:csv,txt',
                    function ($attribute, $value, $fail) {
                        $scanner = Scanner::find($this->scanner);

                        if (is_null($scanner)) {
                            return;
                        }

                        if ($scanner->attlog_file !== pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME)) {
                            $fail("Scanner and $attribute mismatch.");
                        }
                    }
                ],
            ];
        }

        return [
            'scanner_id' => 'required|exists:scanners,id',
            'uid' => 'required|alpha_num',
            'time' => [
                'required',
                'date',
                Rule::unique('timelogs')
                    ->where('scanner_id', $this->scanner_id)
                    ->where('uid', $this->uid)
                    ->where('state', $this->state),
            ],
            'state' => [
                'required',
                Rule::in(array_merge(Timelog::IN, Timelog::OUT)),
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
