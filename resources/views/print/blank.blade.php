<?php

use App\Actions\GenerateQrCode;
use App\Models\Holiday;
use App\Models\Schedule;
use Illuminate\Support\Carbon;

$size = isset($size)  ? mb_strtolower($size) : 'folio';
$month = Carbon::parse($month);
$weekends ??= true;
$holidays ??= true;
$single ??= false;

$seal = file_exists(storage_path('app/public/'.settings('seal')))
    ? base64_encode(file_get_contents(storage_path('app/public/'.settings('seal'))))
    : null;

$timestamp ??= now();
$supervisor = ($supervisor ?? true)
    ? $employee->currentDeployment?->supervisor?->titled_name
    : false;

$head = ($head ?? true)
    ? ($head = $employee->currentDeployment?->office?->head)?->is($employee) ? null : $head?->titled_name
    : false;

if ($schedule ??= true) {
    $schedules = Schedule::search(
        employee: $employee,
        date: $month->clone()->startOfMonth(),
        until: $month->clone()->endOfMonth(),
    );

    $time = function (string $week) use ($schedules) {
        return match (true) {
            $schedules?->$week?->count() === 1 => $schedules?->$week?->first()->time,
            $schedules?->$week?->filter(fn ($schedule) => $schedule->{str($week)->singular()->toString()})->count() === 1 => $schedules?->$week?->filter(fn ($schedule) => $schedule->{str($week)->singular()->toString()})->first()?->time,
            default => 'as required'
        };
    };
}

$holiday = Holiday::whereBetween('date', [$month->clone()->startOfMonth(), $month->clone()->endOfMonth()])->get();
?>

@extends('print.layout')

@section('content')
    <div
        @style([
            'display:flex',
            'align-items:center',
            'justify-content:center',
        ])
    >
        @for($side = 0; $side < ($single ? 1 : 2); $side++)
            <div
                @style([
                    'width:100%',
                    'border:none;' => $size === 'a4',
                    'border-width:1pt' => ! $single,
                    'border-style:none dashed none none' => $side === 0 && ! $single,
                    'border-style:none none none dashed' => $side === 1 && ! $single,
                ])
            >
                <table
                    border="0"
                    cellpadding="0"
                    cellspacing="0"
                    @style([
                        'border-collapse:collapse',
                        'table-layout:fixed',
                        'width:fit-content',
                        'margin-left:0pt' => $side == 0 && $size == 'letter',
                        'margin-left:auto;margin-right:1pt' => $side == 1 && $size == 'letter',
                        'margin-left:30pt' => $side == 0 && $size == 'a4',
                        'margin-left:auto;margin-right:31pt' => $side == 1 && $size == 'a4',
                        'margin:auto!important' => ! in_array($size, ['letter', 'a4']),
                    ])
                >
                    <col width="57" span="6">
                    <tr>
                        <td colspan="6" class="relative">
                            <span class="absolute" style="font-size:4.65pt;opacity:0.05;right:0.65pt;">ᜑᜊᜄᜆᜅ᜔ ᜇᜊᜏ᜔</span>
                            @if ($seal)
                                <img
                                    src="data:image/png;base64,{{ $seal }}"
                                    alt="{{ settings('name') }}"
                                    class="absolute"
                                    style="width:36pt;opacity:0.2;top:30pt;right:0;"
                                >
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="italic font-xs arial" colspan="6">
                            Civil Service Form No. 48
                        </td>
                    </tr>
                    <tr>
                        <td class="relative center bahnschrift font-xl bold" colspan="6">
                            <span class="absolute nowrap" style="top:8pt;left:0;right:0;margin:auto;">
                                DAILY TIME RECORD
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="relative center font-xs bold" colspan="6">
                            <span class="absolute" style='font-variant-ligatures:normal;font-variant-caps:normal;orphans:2;widows:2;-webkit-text-stroke-width:0px;text-decoration-thickness:initial;text-decoration-style:initial;text-decoration-color:initial;top:15pt;left:0;right:0;margin:auto;'>
                                -----o0o-----
                            </span>
                        </td>
                    </tr>
                    <tr><td colspan="6"></td></tr>
                    <tr><td colspan="6"></td></tr>
                    <tr>
                        <td class="underline uppercase courier font-lg center bold" colspan="6" style="text-decoration: none;">
                            @if ($name)
                                {{ $employee->name }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="courier top center font-xs" colspan="6">
                            Employee
                        </td>
                    </tr>
                    <tr>
                        <td class="arial font-xs bottom right" colspan="2" style="padding-bottom:2.5pt;padding-right:10pt;">
                            For the month of:
                        </td>
                        <td class="underline font-md courier bold left" colspan="4" style="text-decoration: none;">
                            {{ $month->format('Y F') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="font-xs left middle arial" colspan="2" rowspan="2" height="40">Official hours for <br> arrival &amp; departure </td>
                        <td class="relative arial font-xs bottom left nowrap" colspan="1">
                            <span class="absolute" style="bottom:1pt;left:-11pt;">
                                Weekdays
                            </span>
                        </td>
                        <td colspan="3" class="underline courier bold font-sm bottom left nowrap" style="text-decoration:none;letter-spacing:-0.1pt;">
                            @if ($schedule)
                                {{ $time('weekdays') ?? 'as required' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="relative arial font-xs bottom left" colspan="1">
                            <span class="absolute" style="bottom:1pt;left:-11pt;">
                                Weekends
                            </span>
                        </td>
                        <td colspan="3" class="underline courier bold font-sm bottom left nowrap" style="text-decoration:none;letter-spacing:-0.1pt;">
                            @if ($schedule)
                                {{ $time('weekends') ?? 'as required' }}
                            @endif
                        </td>
                    </tr>
                    <tr><td colspan="6"></td></tr>
                    <tr class="font-sm bold">
                        <td class="border center middle courier" rowspan="2" height="42" width="58">DAY</td>
                        <td class="border center middle courier" colspan="2" width="116">AM</td>
                        <td class="border center middle courier" colspan="2" width="116">PM</td>
                        <td class="border center middle courier" rowspan="2" width="58">Under<br>time</td>
                    </tr>
                    <tr class="font-sm bold">
                        <td class="border courier center" width="58" style="font-size:7.5pt;">Arrival</td>
                        <td class="border courier center" width="58" style="font-size:7.5pt;">Departure</td>
                        <td class="border courier center" width="58" style="font-size:7.5pt;">Arrival</td>
                        <td class="border courier center" width="58" style="font-size:7.5pt;">Departure</td>
                    </tr>

                    @for ($day = 1; $day <= 31; $day++)
                        @php($date = $month->clone()->setDay($day))

                        @php($event = $holiday->first(fn ($holiday) => $holiday->date->isSameDay($date))?->name)

                        <tr
                            @class([
                                'holiday' => !$date->isWeekend() && $event,
                                'weekend' => $date->isWeekend() && $date->day === $day,
                            ])
                        >
                            <td class="border right courier bold font-sm" style="padding-right:14pt;padding-top:1pt;">
                                &nbsp; {{  $day === $date->day ? $day : '--' }}
                            </td>
                            @if ($date->day === $day && ($holidays && $event || $weekends && $date->isWeekend()))
                                <td class="border cascadia nowrap font-sm" colspan="5" style="padding-left:10pt;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $date->format('l') . ($event ? " - $event" : '') }}
                                </td>
                            @else
                                <td class="border"></td>
                                <td class="border"></td>
                                <td class="border"></td>
                                <td class="border"></td>
                                <td class="border"></td>
                            @endif
                        </tr>
                    @endfor

                    <tr style="height:10pt"></tr>
                    <tr>
                        <td colspan="2" class="font-md courier right bold" style="padding-right:12pt;padding-bottom:2pt;">TOTAL:</td>
                        <td colspan="4" @class(["underline courier left" ])></td>
                    </tr>

                    @if ($size === 'legal')
                        <tr><td colspan="6"></td></tr>
                    @endif

                    <tr>
                        <td class="italic font-xs arial" colspan="6" rowspan="3">
                            I certify on my honor that the above is a true and correct report of the hours of work performed,
                            record of which was made daily at the time of arrival and departure from office.
                        </td>
                    </tr>
                    <tr><td colspan="6"></td></tr>
                    @if ($size === 'legal')
                        <tr><td colspan="6"></td></tr>
                    @endif

                    <tr><td colspan="6"></td></tr>
                    <tr><td colspan="6"></td></tr>
                    <tr><td colspan="6"></td></tr>
                    <tr>
                        <td class="underline" colspan=6 style="height:22.5pt;"></td>
                    </tr>
                    <tr><td class="bahnschrift-light top center font-xs" colspan="6">Employee Signature</td></tr>
                    <tr><td colspan="6"></td></tr>
                    <tr><td class="italic arial font-xs" colspan="6">Verified as to the prescribed office hours:</td></tr>
                    <tr><td colspan="6" style="height:22.5pt;"></td></tr>

                    @if ($size === 'legal')
                        <tr><td colspan="6"></td></tr>
                    @endif

                    <tr>
                        <td colspan="6" @class(['center font-sm', 'underline' => $supervisor])>
                            @if ($supervisor)
                                {{ $supervisor }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="bahnschrift-light top center font-xs" colspan="6">
                            @if ($supervisor)
                                Immediate Supervisor
                            @endif
                        </td>
                    </tr>
                    <tr><td colspan="6" style="height:22.5pt;"></td></tr>

                    @if ($size === 'legal')
                        <tr><td colspan="6"></td></tr>
                    @endif

                    <tr><td colspan="6"></td></tr>
                    <tr>
                        <td colspan="6" class="underline center font-sm">
                            @if ($head)
                                {{ $head }}
                            @endif
                        </td>
                    </tr>
                    <tr><td class="bahnschrift-light top center font-xs" colspan="6">Office Head</td></tr>
                    <tr><td colspan="6"></td></tr>

                    @if ($size === 'legal')
                        <tr><td colspan="6"></td></tr>
                    @endif
                </table>
            </div>
        @endfor
    </div>
@endsection


@push('head')
    <style>
        @media print {
            @page {
                margin: 0;
                size: {{
                    match($size) {
                        'a4' => '9in 13in',         // OVERRIDE FOR BETTER FIT
                        'legal' => '8.5in 14in',
                        default => '8.5in 13in',
                    }
                }};
            }
        }
    </style>
@endpush
