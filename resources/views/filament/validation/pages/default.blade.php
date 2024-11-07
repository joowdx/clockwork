<?php
use App\Models\Holiday;

$label = fn ($timesheet) => (trim($timesheet->period) ?: \Carbon\Carbon::parse($timesheet->month)->format('F Y')) .
    ($timesheet->getPeriod() === 'overtimeWork' ? ' (OT)' : '');

$chunk = PHP_INT_MAX;
?>

@foreach ($employees as $employee)

    @php($employee->loadMissing(['timelogs.scanner']))

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
        <div @class(['max-w-[24em] sm:max-w-max lg:max-w-[40em] 2xl:max-w-max']) @style(['overflow:auto'])>
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
                <col width=68 span=10>
                <tr class="font-sm bold">
                    <td class="uppercase bottom courier left">Day</td>
                    <td class="bottom courier left" style="font-size:7.5pt;">Records</td>
                </tr>
                @foreach ($month->range($month->format('Y-m-') . $month->daysInMonth) as $date)
                    @continue($period === 'dates' ? ! in_array($date->format('Y-m-d'), $dates) : $date->day < $from || $date->day > $to)

                    @forelse ($employee->timelogs->filter(fn ($t) => $t->time->isSameDay($date))->sortBy('time')->chunk($chunk) as $timelogs)
                        <tr @class(['font-mono font-sm', 'weekend' => $date->isWeekend()]) style="border-color: #8888 !important; text-decoration: none;">
                            <td class="px-2" style="padding-right:8pt">
                                {{ $date->format('d D') }}
                            </td>
                            @foreach ($timelogs->take(9) as $timelog)
                                <td class="relative text-sm">
                                    <span
                                        @class(['font-sm nowrap'])
                                        @style([
                                            "text-color:{$timelog->scanner->foregroundColor}!important;",
                                            "background-color:{$timelog->scanner->backgroundColor}!important;",
                                            'border-radius:0.2em',
                                        ])
                                    >
                                        {{ $timelog->time->format('H:i') }}
                                    </span>

                                    @if ($timelog->recast)
                                        <span class="absolute" style="left:-0.5em;">
                                            <sup>‽</sup>
                                        </span>
                                    @endif

                                    <span class="absolute">
                                        <sup>{{ match(true) { $timelog->in => 'i', $timelog->out => 'o', default => 'u' } }}</sup><sub>{{ $timelog->mode->getCode() }}</sub>
                                    </span>
                                </td>
                            @endforeach
                            @if ($timelogs->count() < 9)
                                <td colspan="{{ 9 - $timelogs->count() }}"></td>
                            @endif
                        </tr>
                    @empty
                        <tr @class(['font-mono font-sm', 'weekend' => $date->isWeekend()])>
                            <td class="px-2" style="padding-right:8pt">
                                {{ $date->format('d D') }}
                            </td>
                            <td colspan="9"></td>
                        </tr>
                    @endforelse
                @endforeach
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
