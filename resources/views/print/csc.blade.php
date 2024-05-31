@extends('print.layout')

@php($size = isset($size)  ? mb_strtolower($size) : 'folio')

@php($preview ??= false)

@section('content')
    @foreach ($timesheets as $timesheet)
        <div
            @class([
                'pagebreak',
                'mb-8' => $preview && !$loop->last
            ])
            @style([
                'display:flex',
                'align-items:center',
                'justify-content:center',
            ])
        >
            @for($side = 0; $side < ($preview ? 1 : 2); $side++)
                <div
                    @style([
                        'width:100%',
                        'border-width:1pt' => ! $preview,
                        'border-style:none dashed none none' => $side === 0,
                        'border-style:none none none dashed' => $side === 1,
                    ])
                >
                    <table
                        border=0
                        cellpadding=0
                        cellspacing=0
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
                        @if($preview)
                            <col width=65 span=7>
                        @else
                            <col width=57 span=6>
                            <tr>
                                <td colspan=6 class="relative">
                                    <span class="absolute" style="font-size:4.65pt;opacity:0.05;right:0;">ᜑᜊᜄᜆᜅ᜔ ᜇᜊᜏ᜔</span>
                                    <img src="{{ url('storage/'.settings('seal')) }}" alt="davao-del-sur" class="absolute" style="width:36pt;opacity:0.2;top:15pt;right:0;">
                                </td>
                            </tr>
                            <tr>
                                <td class="italic font-xs arial" colspan=6 >
                                    Civil Service Form No. 48
                                </td>
                            </tr>
                            <tr>
                                <td class="center bahnschrift font-xl bold" colspan=6>DAILY TIME RECORD</td>
                            </tr>
                            <tr>
                                <td class="center font-xs bold" colspan=6>
                                    <span style='font-variant-ligatures: normal;font-variant-caps: normal;orphans: 2;widows: 2;-webkit-text-stroke-width: 0px;text-decoration-thickness: initial;text-decoration-style: initial;text-decoration-color: initial'>
                                        -----o0o-----
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=6></td>
                            </tr>
                        @endif
                        <tr>
                            <td class="underline uppercase courier font-lg center bold" colspan={{ $preview ? 7 : 6 }} style="text-decoration: none;">
                                {{ $timesheet->employee->name }}
                            </td>
                        </tr>
                        <tr>
                            <td class="courier top center font-xs" colspan={{ $preview ? 7 : 6 }}>
                                Employee
                            </td>
                        </tr>
                        <tr>
                            <td class="arial font-xs bottom right" colspan={{ $preview ? 3 : 2 }} style="padding-bottom:2.5pt;padding-right:10pt;">
                                For the month of:
                            </td>
                            <td class="underline font-md courier bold center" colspan=4 style="text-decoration: none;">
                                {{ $timesheet->period }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-xs left middle arial" colspan={{ $preview ? 3 : 2 }} rowspan=2 height=40>Official hours for <br> arrival &amp; departure </td>
                            <td class="relative arial font-xs bottom left nowrap" colspan=1>
                                <span class="absolute" style="bottom:1pt;left:-11pt;">
                                    Weekdays
                                </span>
                            </td>
                            <td colspan=3 class="underline courier bold font-sm bottom left nowrap" style="text-decoration: none;">
                                {{ $timesheet->details['schedule']['weekdays'] ?? 'as required' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="relative arial font-xs bottom left" colspan=1>
                                <span class="absolute" style="bottom:1pt;left:-11pt;">
                                    Weekends
                                </span>
                            </td>
                            <td colspan=3 class="underline courier bold font-sm bottom left nowrap" style="text-decoration: none;">
                                {{ $timesheet->details['schedule']['weekends'] ?? 'as required' }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr class="font-sm bold">
                            <td class="border center middle courier" rowspan=2 height=42 width=58>DAY</td>
                            <td class="border center middle courier" colspan=2 width=116>AM</td>
                            <td class="border center middle courier" colspan=2 width=116>PM</td>
                            <td class="border center middle courier" rowspan=2 width=58>Under<br>time</td>
                            @if ($preview)
                                <td class="border center middle courier" rowspan=2 width=58>Over<br>time</td>
                            @endif
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

                            @if ($timesheet->from <= $day && $day <= $timesheet->to)
                                <tr
                                    @class([
                                        'weekend' => $date->isWeekend() && ! $timetable?->holiday,
                                        'holiday' => $timetable?->holiday,
                                        'absent' => $timetable?->absent,
                                        // 'invalid' => $timetable?->invalid,
                                        'font-sm' => true
                                    ])
                                >
                                    <td
                                        @class([
                                            'border right bold',
                                            $preview ? 'font-mono' : 'courier',
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
                                                    'courier' => !$preview,
                                                    'font-mono' => $preview,
                                                ])
                                                @style([
                                                    'padding-top:1pt',
                                                    $preview ? 'padding-right:5pt' : 'padding-left:5pt',
                                                    'background-color:' . ($timetable?->punch[$punch]['background'] ?? 'transparent'),
                                                    'text-color:' . ($timetable?->punch[$punch]['foreground'] ?? 'black'),
                                                    'background-color: #FF9D2834' => $timetable?->punch[$punch]['missed'] ?? false,
                                                ])
                                            >
                                                {{ substr($timetable->punch[$punch]['time'] ?? '', 0, strrpos($timetable?->punch[$punch]['time'] ?? '', ":")) }}
                                                @if (isset($timetable->punch[$punch]['undertime']) && ($ut = $timetable?->punch[$punch]['undertime']) > 0)
                                                    <span class="undertime-badge">
                                                        {{ $ut }}
                                                    </span>
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
                                    @elseif($timetable?->regular === false || $date->isWeekend())
                                        <td colspan=4 @class(['border cascadia', $preview ? 'text-left px-4' : 'center'])>
                                            {{ $timetable?->holiday ?: $date->format('l') }}
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
                                            $preview ? 'font-mono' : 'courier',
                                        ])
                                        style="padding-right:14pt;"
                                    >
                                        {{ $timetable?->undertime }}
                                    </td>
                                    @if($preview)
                                        <td
                                            @class([
                                                'border right bold',
                                                $preview ? 'font-mono' : 'courier',
                                            ])
                                            style="padding-right:14pt;"
                                        >
                                            {{ $timetable?->overtime }}
                                        </td>
                                    @endif
                                </tr>
                            @else
                                <tr>
                                    <td class="border right courier bold font-sm"
                                        style="padding-right:14pt;padding-top:1pt;"
                                    >
                                        &nbsp; {{  $day === $date->day ? $day : '--' }}
                                    </td>
                                    <td class="border courier" colspan={{ $preview ? 6 : 5 }} style="padding-left:11pt;">

                                    </td>
                                </tr>
                            @endif
                        @endfor
                        @if (! $preview)
                            <tr style="height:10pt"> </tr>
                            <tr>
                                <td colspan=2 class="font-md courier right bold" style="padding-right:10pt;padding-bottom:2pt;">TOTAL:</td>
                                <td colspan=4 class="underline courier font-md left bold">
                                    {{ $timesheet->total }}
                                </td>
                            </tr>
                            @if ($size === 'legal')
                                <tr>
                                    <td colspan=6></td>
                                </tr>
                            @endif
                            <tr>
                                <td class="italic font-xs arial" colspan=6 rowspan=3>
                                    I certify on my honor that the above is a true and correct report of the hours of work performed,
                                    record of which was made daily at the time of arrival and departure from office.
                                </td>
                            </tr>
                            <tr>
                                <td colspan=6></td>
                            </tr>
                            @if ($size === 'legal')
                                <tr>
                                    <td colspan=6></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan=6 style="height:22.5pt;"></td>
                            </tr>
                            <tr>
                                <td class="underline" colspan=6></td>
                            </tr>
                            <tr>
                                <td class="bahnschrift-light top center font-xs" colspan=6>Employee's Signature</td>
                            </tr>
                            <tr>
                                <td class="italic arial font-xs" colspan=6>Verified as to the prescribed office hours:</td>
                            </tr>
                            <tr>
                                <td colspan=6 style="height:22.5pt;"></td>
                            </tr>
                            @if ($size === 'legal')
                                <tr>
                                    <td colspan=6></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan=6 class="underline center font-sm">
                                    {{ $timesheet->details['supervisor'] ?? '' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="bahnschrift-light top center font-xs" colspan=6>Supervisor</td>
                            </tr>
                            <tr>
                                <td colspan=6 style="height:22.5pt;"></td>
                            </tr>
                            @if ($size === 'legal')
                                <tr>
                                    <td colspan=6></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan=6 class="underline center font-sm">
                                    {{ $timesheet->details['head'] ?? '' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="bahnschrift-light top center font-xs" colspan=6>Department Head</td>
                            </tr>
                            <tr>
                                <td colspan=6></td>
                            </tr>
                            @if ($size === 'legal')
                                <tr>
                                    <td colspan=6></td>
                                </tr>
                            @endif
                            <tr style="width:100%;border-width:0;border-top-width:0.5pt;border-style:dashed;border-color:#0007!important;">
                                <td colspan=1 class="relative">
                                    @if ($timesheet->timetables->some(fn($timetable) => collect($timetable->punch)->some(fn ($punches) => isset($punches['next']) || isset($punches['previous']))))
                                        <div class="consolas" style="font-size:4.0pt;opacity:0.5;">
                                            N = Next day <br>
                                            P = Previous day
                                        </div>
                                    @endif
                                    <div class="absolute font-xxs consolas" style="opacity:0.3;transform:rotate(270deg);left:-17pt;top:10pt;">

                                    </div>
                                    <div class="absolute consolas" style="opacity:0.3;font-size:5pt;top:0pt;">

                                    </div>
                                </td>
                                <td colspan=4 class="relative">
                                    @if(($office = auth()->user()?->employee?->currentDeployment?->office)?->logo)
                                        <img src="{{ url('storage/'.$office->logo) }}" alt="{{ $office->code }}" class="absolute" style="width:36pt;height:auto;opacity:0.15;margin:auto;top:-10pt;left:0;right:0;">
                                    @endif
                                </td>
                                <td colspan=1></td>
                            </tr>
                            <tr>
                                <td colspan=1></td>
                                <td class="relative underline font-xs center bottom bold courier" colspan=4 style="color:#000A;border-color:#0007!important;">
                                    @includeWhen($signature ??= null, 'print.signature', ['signature' => $signature, 'signed' => $signed ?? false])
                                    {{ auth()->user()?->name }}
                                </td>
                                <td colspan=1></td>
                            </tr>
                            <tr>
                                <td colspan=1> </td>
                                <td class="font-xxs center courier top" colspan=4 style="color:#000A;">
                                    {{ auth()->user()?->employee?->designation ?? '' }}
                                </td>
                                <td class="relative" colspan=1>
                                    <div class="absolute consolas" style="opacity:0.5;bottom:8pt;right:0;font-size:4.0pt;">
                                        {{ now()->format('Y-m-d|H:i') }}
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            @endfor
        </div>
    @endforeach
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

@if ($preview)
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
@endif
