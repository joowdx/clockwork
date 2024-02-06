<?php

namespace App\Http\Requests\Api;

use App\Models\Employee;
use App\Models\Timelog;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class TimelogUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'timelogs' => [
                'array',
                'required',
                'max:4',
            ],
            'timelogs.*.id' => [
                'required',
                'distinct',
                'string',
                'ulid',
                'exists:timelogs,id',
            ],
            'timelogs.*.state' => [
                'required',
                'string',
                'max:255',
                'in:in,out',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $timelogs = $this->timelogs();

                if ($timelogs->contains(fn ($t) => ! $t->official)) {
                    $validator->errors()->add('timelogs', 'Only official timelogs can be corrected.');
                }

                if ($timelogs->unique(fn ($t) => $t->time->format('Y-m-d'))->count() > 1) {
                    $validator->errors()->add('timelogs', 'Timelogs to be corrected must be of the same date.');
                }

                if ($timelogs->load('employee')->some(fn ($t) => $t->employee->id !== $this->route('employee')->id)) {
                    $validator->errors()->add('timelogs', 'Timelogs to be corrected must be from the same employee.');
                }
            }
        ];
    }

    public function day(): Carbon
    {
        return $this->timelogs()->first()->time->clone()->startOfDay();
    }

    public function find(string $id): array
    {
        return collect($this->timelogs)->first(fn ($t) => $t['id'] === $id);
    }

    public function timelogs(): Collection
    {
        return Timelog::find(collect($this->timelogs)->pluck('id'))
            ->load('correction', 'original');
    }
}
