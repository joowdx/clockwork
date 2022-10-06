<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class EnrollmentRequest extends FormRequest
{
    public function attributes()
    {
        return [
            'employees.*.uid' => 'UID',
            'scanners.*.uid' => 'UID',
        ];
    }

    public function rules()
    {
        return [
            'employee' => [
                'required_without:scanner',
                'exists:employees,id',
            ],
            'employees' => [
                'required_with:scanner',
                'array'
            ],
            'employees.*.uid' => [
                'bail',
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
                'bail',
                'sometimes',
                'required',
                'numeric',
                fn ($a, $v, $f) => $this->uid($a, $v, $f),
            ],
        ];
    }

    private function uid(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->scanner === null) {
            return;
        }

        $scanner = $this->scanner ?? explode('.', $attribute)[1];

        $employee = $this->employee ?? explode('.', $attribute)[1];

        $enrollment = Enrollment::whereScannerId($scanner)->whereEmployeeId($employee)->first('uid');

        if ($enrollment === null || $enrollment->uid === (int) $value) {
            return;
        }

        if ($existing = Enrollment::whereScannerId($scanner)->whereUid($value)->first()) {
            $fail('Selected UID is already taken by ' . $existing->employee->nameFormat->shortStartLastInitialFirst);
        }
    }
}
