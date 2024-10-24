<?php
use App\Models\Holiday;

$label = fn ($timesheet) => (trim($timesheet->period) ?: \Carbon\Carbon::parse($timesheet->month)->format('F Y')) .
    ($timesheet->getPeriod() === 'overtimeWork' ? ' (OT)' : '');

$holidays = fn ($month) => Holiday::search($month);
?>

@foreach ($timesheets as $timesheet)
    <div
        @class([
            'overflow-hidden',
            'no-print',
            'mb-8' => !$loop->last
        ])
        @style([
            'display:flex',
            'align-items:center',
            'justify-content:center',
        ])
    >

        <div @class(['max-w-[24em] sm:max-w-max lg:max-w-[23em] 2xl:max-w-max']) @style(['overflow:auto'])>
            <table
                cellpadding=0
                cellspacing=0
                @style([
                    'border-collapse:separate',
                    'table-layout:fixed',
                    'width:fit-content',
                    'border-spacing:0 0.2em'
                ])
            >
                <col width=62 span=7>
                <tr class="font-sm bold">
                    <td class="px-1 uppercase bottom courier left">Day</td>
                    <td class="px-2 bottom courier" style="font-size:7.5pt;">Arrival</td>
                    <td class="px-2 bottom courier" style="font-size:7.5pt;">Departure</td>
                    <td class="px-2 bottom courier" style="font-size:7.5pt;">Arrival</td>
                    <td class="px-2 bottom courier" style="font-size:7.5pt;">Departure</td>
                    <td class="px-2 bottom courier right" style="font-size:7.5pt;">Undertime</td>
                    <td class="px-2 bottom courier right" style="font-size:7.5pt;">Overtime</td>
                </tr>
                @php($calendar = $holidays($timesheet->month))

                @for ($day = 1; $day <= 31; $day++)
                    @php($date = Carbon\Carbon::parse($timesheet->month)->setDay($day))

                    @php($timetable = $timesheet->{$timesheet->getPeriod()}->first(fn($timetable) => $timetable->date->isSameDay($date)))

                    @php($holiday = $timetable?->holiday ?: $calendar->first(fn ($holiday) => $holiday->date->isSameDay($date))?->name)

                    @if ($timesheet->from <= $day && $day <= $timesheet->to)
                        <tr
                            @class([
                                'weekend' => $date->isWeekend() && (@$misc['weekends'] ?? true) && ! $timetable?->holiday,
                                'holiday' => ($holiday && (@$misc['holidays'] ?? true)),
                                'absent' => $timetable?->absent && (@$misc['highlights'] ?? true) && (@$misc['absences'] ?? true),
                                'invalid' => $timetable?->invalid,
                                'font-sm',
                            ])
                        >
                            <td @class(['right', 'font-mono']) style="padding-right:8pt;">
                                {{ $date->format('d D') }}
                            </td>
                            @if ($timetable?->present)
                                @foreach(['p1', 'p2', 'p3', 'p4'] as $punch)
                                    <td
                                        @class([
                                            'px-2',
                                            'relative nowrap',
                                            'font-mono',
                                            'invalid' => ($timetable?->punch[$punch]['missed'] ?? false) && (@$misc['highlights'] ?? true),
                                        ])
                                        @style([
                                            'padding-right:5pt',
                                        ])
                                    >
                                        <span @style([
                                            'background-color:' . ($timetable?->punch[$punch]['background'] ?? 'transparent'),
                                            'text-color:' . ($timetable?->punch[$punch]['foreground'] ?? 'black'),
                                            'width:fit-content',
                                            'height:fit-content',
                                            'border-radius:0.2em',
                                        ])>
                                            {{ substr($timetable->punch[$punch]['time'] ?? '', 0, strrpos($timetable?->punch[$punch]['time'] ?? '', ":")) }}
                                        </span>
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
                                <td colspan=4 @class(['cascadia nowrap', 'text-left px-2']) style="overflow:hidden;text-overflow:ellipsis;">
                                    {{ $holiday ?: $date->format('l') }}
                                </td>
                            @else
                                @for ($cell = 0; $cell < 4; $cell++)
                                    <td class="">

                                    </td>
                                @endfor
                            @endif
                            <td @class(['right', 'font-mono', 'px-2'])>
                                {{ $timetable?->undertime }}
                            </td>
                            <td @class(['right', 'font-mono', 'px-2'])>
                                {{ $timetable?->overtime }}
                            </td>
                        </tr>
                    @endif
                @endfor
            </table>
        </div>
    </div>
@endforeach

@if($styles ?? true)
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
@else
    <style>
        {!! File::get(base_path('resources/css/print.css')) !!}
        {!! File::get(base_path('resources/css/fonts.css')) !!}

        td:first-child,
        th:first-child {
            border-radius: 0.3em 0 0 0.3em;
        }

        td:last-child,
        th:last-child {
            border-radius: 0 0.3em 0.3em 0;
        }

        .undertime-badge {
            width: 12pt !important;
            height: 12pt !important;
            color: #222!important;
            background-color: #F336!important;
        }
        @media (prefers-color-scheme: dark) {
            .undertime-badge {
                color: #FFF!important;
                background-color: #F33!important;
            }
            td {
                border-color: #333 !important;
            }
        }
    </style>
@endif
