<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Rules\Password;

class PinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match (strtolower($this->method())) {
            'put' => ! empty($this->route('employee')->pin),
            default => true,
        };
    }

    public function messages(): array
    {
        return match($this->method()) {
            default => [
                'scanners.*.required' => 'Fields must not be blank.',
                'scanners.*.numeric' => 'Fields must only contain numeric characters.',
                'scanners.*.min' => 'Fields must not be blank and must be a positive integer.',
            ]
        };
    }

    public function rules(): array
    {
        return match($this->method()) {
            'POST' => [
                'scanners' => 'bail|required|array',
                'scanners.*' => [
                    'bail',
                    'required',
                    'numeric',
                    'min:1',
                ],
                'pin' => [
                    'bail',
                    'required',
                    'confirmed',
                    'numeric',
                    (new Password)->length(4)->requireNumeric(),
                    function ($attribute, $value, $fail) {
                        if (count(array_unique(str_split($value))) === 1) {
                            $fail("Your $attribute must not be a single repeating digit.");
                        }

                        if(in_array($value, [1234, 4321, 9876, 2580, 9630, 1212, 1122, 6969, 0101, 123456, 654321])) {
                            $fail("Your $attribute must not be easily guessable.");
                        };
                    },
                ],
            ],
            'PUT' => [
                'current_pin' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        if (! Hash::check($value, $this->route('employee')->pin)) {
                            $fail("Incorrect " . str($attribute)->replace('_', ' ') . ".");
                        }
                    },
                ],
                'pin' => [
                    'required',
                    'confirmed',
                    'numeric',
                    (new Password)->length(4)->requireNumeric(),
                    function ($attribute, $value, $fail) {
                        if (count(array_unique(str_split($value))) === 1) {
                            $fail("Your $attribute must not be a single repeating digit.");
                        }

                        if(in_array($value, [1234, 4321, 9876, 2580, 9630, 1212, 1122, 6969, 0101, 123456, 654321])) {
                            $fail("Your $attribute must not be easily guessable.");
                        };

                        if($value === $this->current_pin && Hash::check($value, $this->route('employee')->pin)) {
                            $fail("Your $attribute must not your current pin.");
                        }
                    },
                ],
            ],
            default => [
                'scanners' => 'bail|required|array',
                'scanners.*' => [
                    'bail',
                    'required',
                    'numeric',
                    'min:1',
                ],
                'pin' => [
                    'bail',
                    'required',
                    'confirmed',
                    'numeric',
                    (new Password)->length(4)->requireNumeric(),
                    function ($attribute, $value, $fail) {
                        if (count(array_unique(str_split($value))) === 1) {
                            $fail("Your $attribute must not be a single repeating digit.");
                        }

                        if(in_array($value, [1234, 4321, 9876, 2580, 9630, 1212, 1122, 6969, 0101, 123456, 654321])) {
                            $fail("Your $attribute must not be easily guessable.");
                        };

                        if(Hash::check($value, $this->route('employee')->pin)) {
                            $fail("Your $attribute must not your current pin.");
                        }
                    },
                ],
            ] ,
        };
    }

    protected function after(): array
    {
        return [
            function (Validator $validator) {
                $scanners = $this->route('employee')->scanners()->where('enabled', true)->get();

                $failed = $scanners->some(fn ($scanner) => @$this->scanners[$scanner->id] !==  $scanner->pivot->uid);

                if($failed && empty($validator->errors()->messages())) {
                    $validator->errors()->add('scanners', 'Given data did not match our records.');
                }
            }
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->isMethod('POST')) {
            $scanners = $this->route('employee')->scanners()->where('enabled', true)->get();

            $this->merge([
                'scanners' => $scanners->mapWithKeys(fn ($scanner) => [ $scanner->id => @$this->scanners[$scanner->id] ])->toArray()
            ]);
        }
    }

    protected function failedValidation(ValidatorContract $validator): never
    {
        $errors = collect($validator->errors()->messages())->undot();

        $message = collect()
            ->when($errors->has('current_pin'), fn ($message) => $message->put('current_pin', join(' ', $errors->get('current_pin'))))
            ->when($errors->has('pin'), fn ($message) => $message->put('pin', join(' ', $errors->get('pin'))))
            ->when($errors->has('scanners'), fn ($message) => $message->put('scanners', collect($errors['scanners'])->flatten()->unique()->join(' ')))
            ->toArray();

        throw ValidationException::withMessages($message);
    }

}
