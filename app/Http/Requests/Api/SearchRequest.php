<?php

namespace App\Http\Requests\Api;

use App\Models\Employee;
use App\Services\TimeLogService;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class SearchRequest extends FormRequest
{
    public function __construct(
        private TimeLogService $timelog,
    ) {
    }

    public function rules(): array
    {
        return [
            'employees' => 'array|required',
            'employees.*.name' => 'array|required',
            'employees.*.name.first' => 'string|required|max:30',
            'employees.*.name.middle' => 'string|nullable|max:30',
            'employees.*.name.last' => 'string|required|max:30',
            'employees.*.name.extension' => 'string|nullable|max:30',

            'period' => 'required|in:custom,full,1st,2nd',
            'month' => 'required_if:period,full,1st,2nd|date|date_format:Y-m',
            'from' => 'required_if:period,custom|date',
            'to' => 'required_if:period,custom|date',

            'weekdays.excluded' => 'sometimes|boolean',
            'weekdays.am.in' => 'sometimes|nullable|date_format:H:i',
            'weekdays.am.out' => 'sometimes|nullable|string|date_format:H:i',
            'weekdays.pm.in' => 'sometimes|nullable|string|date_format:H:i',
            'weekdays.pm.out' => 'sometimes|nullable|date_format:H:i',
            'weekends.excluded' => 'sometimes|boolean',
            'weekends.am.in' => 'sometimes|nullable|string|date_format:H:i',
            'weekends.am.out' => 'sometimes|nullable|string|date_format:H:i',
            'weekends.pm.in' => 'sometimes|nullable|string|date_format:H:i',
            'weekends.pm.out' => 'sometimes|nullable|string|date_format:H:i',
            'weekends.regular' => 'sometimes|boolean',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(
            match (strtolower($this->period)) {
                'full', '1st', '2nd' => [
                    'from' => ($month = Carbon::parse($this->month))->setDay(strtolower($this->period) == '2nd' ? 16 : 1),
                    'to' => strtolower($this->period) == '1st' ? $month->clone()->setDay(15)->endOfDay() : $month->clone()->endOfMonth(),
                ],
                'custom' => [
                    'from' => Carbon::parse($this->from)->startOfDay(),
                    'to' => Carbon::parse($this->to)->endOfDay(),
                ],
                null => [
                    'from' => Carbon::parse($this->month)->startOfMonth(),
                    'to' => Carbon::parse($this->month)->endOfMonth(),
                ],
                default => [],
            }
        );
    }

    public function passedValidation(): void
    {
        $this->replace([
            'date' =>  [
                'from' => $this->from->format('Y-m-d'),
                'to' => $this->to->format('Y-m-d'),
            ],
            'employees' => collect($this->employees)->mapWithKeys(function ($employee) {
                @['middle' => $middle, 'extension' => $extension] = $employee['name'];

                if (! is_null($middle) && ! is_null($extension)) {
                    $model = $this->find($employee['name']) ?? $this->find([...$employee['name'], 'extension' => null]) ?? $this->find([...$employee['name'], 'middle' => null]);
                }

                $model = $model ?? $this->find($employee['name']);

                if ($middle && ! $model) {
                    $model = $this->find([...$employee['name'], 'middle' => null]);
                }

                if ($extension && ! $model) {
                    $model = $this->find([...$employee['name'], 'extension' => null]);
                }

                $model?->load([
                    'timelogs' => fn ($q) => $q->whereBetween('time', [$this->from, $this->to]),
                    'timelogs.scanner'
                ]);

                $days = collect(CarbonPeriod::create($this->from, $this->to))->mapWithKeys(function ($date) use ($model) {
                    return [
                        $date->format('Y-m-d') => $model ? @$this->timelog->logsForTheDay($model, $date) : null,
                    ];
                });

                return [
                    $this->formatName($employee['name']) => [
                        'name' => $employee['name'],
                        'employee' => @$model->name_format->fullStartLast,
                        'days' => @$days->map->ut->map->count->filter()->count(),
                        'tardy' => @$days->map->ut->map->total->sum(),
                        'invalid' => $days->contains(fn ($day, $date) => @$day['ut']->invalid && ! $model->absentForTheDay(Carbon::parse($date))),
                    ]
                ];
            }),
        ]);
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }

    protected function find(array $name)
    {
        $query = Employee::query();

        collect($name)->filter()->each(fn ($name, $field) => $query->where("name->$field", strtoupper($name)));

        return $query->first(['id', 'name']);
    }

    protected function formatName(array $name)
    {
        return $this->removeExtraWhitespaces("{$name['first']} {$this->initial(@$name['middle'])} {$name['last']} ".@$name['extension']);
    }

    private function initial(?string $string): string
    {
        return $string ? "{$string[0]}." : '';
    }

    private function removeExtraWhitespaces(?string $string): string
    {
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
