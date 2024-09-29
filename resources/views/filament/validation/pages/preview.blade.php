<?php
use App\Models\Holiday;

$label = fn ($timesheet) => (trim($timesheet->period) ?: \Carbon\Carbon::parse($timesheet->month)->format('F Y')) .
    ($timesheet->getPeriod() === 'overtimeWork' ? ' (OT)' : '');
?>

@foreach ($timesheets as $timesheet)
    <div
        @class([
            'no-print',
            'mb-8' => !$loop->last
        ])
        @style([
            'display:flex',
            'align-items:center',
            'justify-content:center',
        ])
    >
        <div @style(['width:100%'])>
            <table
                border=0
                cellpadding=0
                cellspacing=0
                @style([
                    'border-collapse:collapse',
                    'table-layout:fixed',
                    'width:fit-content',
                    'margin:auto',
                ])
            >
                <col width=65 span=6>
                <tr>
                    <td class="arial bottom left" colspan=2 style="padding-bottom:2.5pt;padding-right:10pt;">
                        Month
                    </td>
                    <td class="font-md courier bold left" colspan=4 style="text-decoration: none;">
                        {{ $label($timesheet) }}
                    </td>
                </tr>
                <tr>
                    <td class="font-xs left middle arial" colspan=2 rowspan=2 height=40 style="line-height:120%;">
                        Official hours for <br> arrival &amp; departure
                    </td>
                    <td class="relative arial font-xs bottom left nowrap" colspan=1>
                        <span class="absolute" style="bottom:1pt;left:0pt;">
                            Weekdays
                        </span>
                    </td>
                    <td colspan=3 class="courier bold font-sm bottom left nowrap" style="text-decoration:none;letter-spacing:-0.1pt;">
                        {{ $timesheet->details['schedule']['weekdays'] ?? 'as required' }}
                    </td>
                </tr>
                <tr>
                    <td class="relative arial font-xs bottom left" colspan=1>
                        <span class="absolute" style="bottom:1pt;left:0pt;">
                            Weekends
                        </span>
                    </td>
                    <td colspan=3 class="courier bold font-sm bottom left nowrap" style="text-decoration:none;letter-spacing:-0.1pt;">
                        {{ $timesheet->details['schedule']['weekends'] ?? 'as required' }}
                    </td>
                </tr>
                <tr class="font-sm bold">
                    <td class="border center middle courier" rowspan=2 height=42 width=58>DAY</td>
                    <td class="border center middle courier" colspan=2 width=116>AM</td>
                    <td class="border center middle courier" colspan=2 width=116>PM</td>
                    <td class="border center middle courier" rowspan=2 width=58>Under<br>time</td>
                </tr>
                <tr class="font-sm bold">
                    <td class="border courier center" width=58 style="font-size:7.5pt;">Arrival</td>
                    <td class="border courier center" width=58 style="font-size:7.5pt;">Departure</td>
                    <td class="border courier center" width=58 style="font-size:7.5pt;">Arrival</td>
                    <td class="border courier center" width=58 style="font-size:7.5pt;">Departure</td>
                </tr>
                @for ($day = 1; $day <= 31; $day++)
                    @php($date = Carbon\Carbon::parse($timesheet->month)->setDay($day))

                    @php($timetable = $timesheet->{$timesheet->getPeriod()}->first(fn($timetable) => $timetable->date->isSameDay($date)))

                    @php($holiday = $timetable?->holiday ?: Holiday::search($date, false)?->name)

                    @if ($timesheet->from <= $day && $day <= $timesheet->to)
                        <tr
                            @class([
                                'weekend' => $date->isWeekend() && (@$misc['weekends'] ?? true) && ! $timetable?->holiday,
                                'holiday' => ($holiday && (@$misc['holidays'] ?? true)),
                                'absent' => $timetable?->absent && (@$misc['highlights'] ?? true) && (@$misc['absences'] ?? true),
                                // 'invalid' => $timetable?->invalid,
                                'font-sm' => true
                            ])
                        >
                            <td
                                @class([
                                    'border right bold',
                                    'font-mono',
                                ])
                                style="padding-right:14pt;padding-top:1pt;"
                            >
                                {{ $day }}
                            </td>
                            @if ($timetable?->present)
                                @foreach(['p1', 'p2', 'p3', 'p4'] as $punch)
                                    <td
                                        width=58
                                        @class([
                                            'relative border nowrap',
                                            'font-mono',
                                            'invalid' => ($timetable?->punch[$punch]['missed'] ?? false) && (@$misc['highlights'] ?? true),
                                        ])
                                        @style([
                                            'padding-top:1pt',
                                            'padding-right:5pt',
                                            'background-color:' . ($timetable?->punch[$punch]['background'] ?? 'transparent'),
                                            'text-color:' . ($timetable?->punch[$punch]['foreground'] ?? 'black'),
                                        ])
                                    >
                                        {{ substr($timetable->punch[$punch]['time'] ?? '', 0, strrpos($timetable?->punch[$punch]['time'] ?? '', ":")) }}
                                        @if (isset($timetable->punch[$punch]['undertime']) && ($ut = $timetable?->punch[$punch]['undertime']) > 0)
                                            <span class="undertime-badge">
                                                {{ $ut }}
                                            </span>
                                        @endif
                                        @if ($timetable->punch[$punch]['recast'] ?? false)
                                            <sup @style([
                                                'font-size:6pt',
                                                'position:absolute',
                                                'top:2pt',
                                                'left:2pt',
                                            ])>
                                                ‽
                                            </sup>
                                        @endif
                                        <sub @style([
                                            'font-size:6pt',
                                            'position:absolute',
                                            'bottom:0',
                                        ])>
                                            @switch(true)
                                                @case($timetable->punch[$punch]['next'] ?? false)
                                                    N
                                                    @break
                                                @case($timetable->punch[$punch]['previous'] ?? false)
                                                    P
                                                    @break
                                            @endswitch
                                        </sub>
                                    </td>
                                @endforeach
                            @elseif($holiday && (@$misc['holidays'] ?? true) || $date->isWeekend() && (@$misc['weekends'] ?? true))
                                <td colspan=4 @class(['border cascadia nowrap', 'text-left px-4']) style="overflow:hidden;text-overflow:ellipsis;">
                                    {{ $holiday ?: $date->format('l') }}
                                </td>
                            @else
                                @for ($cell = 0; $cell < 4; $cell++)
                                    <td class="border">

                                    </td>
                                @endfor
                            @endif
                            <td
                                @class([
                                    'border right bold',
                                    'font-mono',
                                ])
                                style="padding-right:14pt;"
                            >
                                {{ $timetable?->undertime }}
                            </td>
                        </tr>
                    @endif
                @endfor
                <tr></tr>
                <tr>
                    <td colspan=1 class="font-md courier left bold" style="padding-right:12pt;padding-bottom:2pt;">TOTAL</td>
                    <td colspan=5 @class(["underline courier left", $timesheet->getPeriod() === 'overtimeWork' ? 'font-xs' : 'font-md' ])>
                        <div style="display:flex;justify-content:space-between;">
                            {{
                                str(
                                    collect(explode(';', $timesheet->total))
                                        ->map(fn (string $data) => str($data)->wrap('<span>', '</span>')->replace('UT', ''))
                                        ->join('')
                                )->toHtmlString()
                            }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endforeach

@push('head')
    <style>
        .undertime-badge {
            width: 12pt !important;
            height: 12pt !important;
        }
        @media (prefers-color-scheme: dark) {
            .undertime-badge {
                color: gray;
                background-color: #FFF;
            }
            td {
                border-color: #333 !important;
            }
        }
    </style>
@endpush
