<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueryResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'period' => 'required|in:custom,full,1st,2nd',
            'month' => 'required_if:period,full,1st,2nd|date|date_format:Y-m',
            'from' => 'required_if:period,custom',
            'to' => 'required_if:period,custom',
        ];
    }
}
