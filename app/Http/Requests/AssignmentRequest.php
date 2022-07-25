<?php

namespace App\Http\Requests;

use App\Traits\ConfirmsPassword;
use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    use ConfirmsPassword;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->administrator;
    }

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
                    'users' => ['array', 'required'],
                    'users.*' => ['uuid', 'exists:users'],
                    'password' => fn ($att, $pass, $fail) => $this->confirmPassword($pass)
                    ] : ($this->has('user')
                ? [
                    'user' => ['exists:users', 'required_without:scanner,id'],
                    'scanners' => ['array', 'required'],
                    'scanners.*' => ['uuid', 'exists:scanners'],
                    'password' => fn ($att, $pass, $fail) => $this->confirmPassword($pass)
                ] : [
                    'scanner' => ['required_without:user'],
                    'user' => ['required_without:scanner'],
                    'password' => ['required']
                ]);
    }
}
