<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class EnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [
            'scanners.*.uid.required' => 'UID must not be empty.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return $this->isMethod('delete')
        ? [
            'password' => [
                'required',
                'string',
            ]
        ] : [
            'employee' => [
                'required_without:scanner',
                'exists:employees,id',
            ],
            'employees' => [
                'required_with:scanner',
                'array'
            ],
            'employees.*.uid' => [
                'sometimes',
                'required',
                'numeric',
                fn ($a, $v, $f) => $this->uid($a, $v, $f),
            ],
            'scanner' => [
                'required_without:employee',
                'exists:scanners,id',
            ],
            'scanners' => [
                'required_with:employee',
                'array',
            ],
            'scanners.*.uid' => [
                'sometimes',
                'required',
                'numeric',
                fn ($a, $v, $f) => $this->uid($a, $v, $f),
            ],
        ];
    }

    private function key(string $attribute): string
    {
        return explode('.', $attribute)[1];
    }

    private function uid(string $attribute, mixed $value, Closure $fail): void
    {
        $scanner = $this->scanner ?? $this->key($attribute);

        $employee = $this->employee ?? $this->key($attribute);

        $enrollment = Enrollment::whereScannerId($scanner)->whereEmployeeId($employee)->first('uid');

        #ignore no changes
        if ($enrollment?->uid === (int) $value) {
            return;
        }

        switch ($enrollment->doesntExist()) {
            #update
            case false: {
                if ($enrollment = $this->existing($scanner, $value)) {
                    $fail('Selected UID is already taken by ' . $enrollment->employee->nameFormat->shortStartLastInitialFirst);
                }
                break;
            }
            #insert
            default: {
                if ($this->enrolled($scanner, $employee)) {
                    $fail('Can only enroll in a single scanner device once.');
                }
            }
        }
    }

    private function existing(string $scanner, string $uid): ?Enrollment
    {
        return Enrollment::whereScannerId($scanner)->whereUid($uid)->first();
    }

    private function enrolled(string $scanner, string $employee): bool
    {
        return Enrollment::whereScannerId($scanner)->whereEmployeeId($employee)->exists();
    }
}
