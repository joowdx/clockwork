<?php

namespace App\Http\Requests\Fetch;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;

class SendRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'callback' => 'required|url',
            'host' => 'required|string',
            'port' => 'nullable|integer|min:1',
            'pass' => 'nullable|string',
            'month' => 'required|string|date_format:Y-m',
            'user' => 'required|string',
            'token' => ['required', 'string', function ($attribute, $value, $fail) {
                try {
                    decrypt($value);
                } catch (DecryptException) {
                    $fail('Invalid token.');
                }
            }],
        ];
    }

    public function data(): array
    {
        return array_merge($data = $this->safe()->all(), ['token' => decrypt($data['token'])]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'callback' => $this->callback ?? null,
            'host' => $this->host ?? null,
            'port' => $this->port ?? null,
            'pass' => $this->pass ?? null,
            'month' => $this->month ?? null,
            'user' => $this->user ?? null,
            'token' => $this->token ?? null,
        ]);
    }
}
