<?php

namespace App\Http\Requests;

use App\Traits\ConfirmsPassword;
use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    use ConfirmsPassword;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return $this->has('scanner')
                ? [
                    'scanner' => ['exists:scanners,id', 'required_without:user'],
                    'users' => ['required'],
                    'users.*' => ['uuid', 'exists:users,id'],
                    'password' => function ($attribute, $password, $fail) {
                        if (! $this->validatePassword($password)) {
                            $fail(__('The password is incorrect.'));
                        }
                    },
                ] : (
                    $this->has('user')
                    ? [
                        'user' => ['exists:users', 'required_without:scanner,id'],
                        'scanners' => ['required'],
                        'scanners.*' => ['uuid', 'exists:scanners,id'],
                        'password' => function ($attribute, $password, $fail) {
                            if (! $this->validatePassword($password)) {
                                $fail(__('The password is incorrect.'));
                            }
                        },
                    ] : [
                        'scanner' => ['required_without:user'],
                        'user' => ['required_without:scanner'],
                        'password' => ['required'],
                    ]
                );
    }
}
