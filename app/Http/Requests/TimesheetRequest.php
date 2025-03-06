<?php

namespace App\Http\Requests;

use DateTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class TimesheetRequest extends FormRequest
{
    public function rules(): array
    {
        $date = function ($a, $v, $f) {
            if (empty($this->input('month'))) {
                return;
            }

            $month = DateTime::createFromFormat('Y-m', $this->month);

            if ($month && $month->format('Y-m') !== $this->month) {
                return;
            }

            if (($date = DateTime::createFromFormat('Y-m-d', $v)) && $date->format('Y-m-d') !== $v) {
                return $f('The selected date is invalid.');
            }

            if (Carbon::parse($month)->format('Y-m') !== Carbon::parse($v)->format('Y-m')) {
                return $f('The selected date is invalid.');
            }
        };

        return [
            'from' => ['required_if:period,range', 'date_format:Y-m-d', 'before_or_equal:to', $date],
            'to' => ['required_if:period,range', 'date_format:Y-m-d', 'after_or_equal:from', $date],
            'dates.*' => ['required', $date],
            'dates' => 'required_if:period,dates|array',
            'period' => 'required|string|in:1st,2nd,full,regular,overtime,dates,range',
            'month' => array_filter([$this->input('period') !== 'range' ? 'required' : null, 'string', 'date_format:Y-m']),
            'uid.*' => 'required|string',
            'uid' => ['required', function ($a, $v, $f) {
                if (! is_string($v) && ! is_array($v)) {
                    $f('The uid field is invalid.');
                }

                if (is_array($v) && ! count($v)) {
                    $f('The uid field is required.');
                }

                if (is_array($v) && count($v) > 30) {
                    $f('The uid field must not have more than 30 items.');
                }
            }],
        ];
    }
}
