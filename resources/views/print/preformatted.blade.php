<?php

use App\Helpers\NumberRangeCompressor;
use App\Helpers\GetRawAttendancePunch;
use App\Models\Holiday;
use Illuminate\Support\Carbon;

$compressor = app(NumberRangeCompressor::class);

$raw = app(GetRawAttendancePunch::class);

$size = isset($size)  ? mb_strtolower($size) : 'folio';

$preview ??= false;

$single ??= false;

$month = Carbon::parse($month);

if (! $preview) {
    $seal = file_exists(storage_path('app/public/'.settings('seal')))
        ? base64_encode(file_get_contents(storage_path('app/public/'.settings('seal'))))
        : null;

    $office = $user?->employee?->currentDeployment?->office;

    $logo = (@$misc['officer']) && $office?->logo && file_exists(storage_path('app/public/'.$office->logo))
        ? base64_encode(file_get_contents(storage_path('app/public/'.$office->logo)))
        : null;

    $time = now();
}

$label = ($period === 'dates' ? $compressor(collect($dates)->map(fn($date) => Carbon::parse($date)->day)->toArray()) : "$from-$to") .
    Carbon::parse($month)->format(' F Y');

$hasNextDay ??= function (array $timelogs, array $current, ?array $middle = null) {
    $cf = in_array(array_key_first($current), ['p2', 'p4']);
    $pe = in_array(array_key_last($timelogs), ['p1', 'p3']);

    return (@$timelogs['p1'] || @$timelogs['p3']) &&
        !(@$timelogs['p2'] || @$timelogs['p4']) &&
        (@$current['p2'] || @$current['p4']) &&
        !(@$current['p1'] || @$current['p3']) ||
        ($cf || $pe) ||
        ($middle && empty($middle));
};

$hasPreviousDay ??= function (array $timelogs, array $current) {
    $cl = in_array(array_key_last($current), ['p1', 'p3']);
    $nf = in_array(array_key_first($timelogs), ['p2', 'p4']);

    return (@$timelogs['p2'] || @$timelogs['p4']) &&
        !(@$timelogs['p1'] || @$timelogs['p3']) &&
        (@$current['p1'] || @$current['p3']) &&
        !(@$current['p2'] || @$current['p4']) ||
        ($cl || $nf);
};
?>

@extends('print.layout')

@section('content')
    @foreach ($employees as $employee)
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
            @for($side = 0; $side < ($preview || $single ? 1 : 2); $side++)
                <div
                    @style([
                        'width:100%',
                        'border-width:1pt' => ! ($preview || $single),
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
                            <col width=65 span={{ $preview ? 5 : 6 }}>
                        @else
                            <col width=57 span=6>
                            <tr>
                                <td colspan=6 class="relative">
                                    <span class="absolute" style="font-size:4.65pt;opacity:0.05;right:0.65pt;">ᜑᜊᜄᜆᜅ᜔ ᜇᜊᜏ᜔</span>
                                    @if (($deployed = $employee->currentDeployment?->office)?->logo && file_exists(storage_path('app/public/'.$deployed->logo)))
                                        <img
                                            src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/'.$deployed->logo))) }}"
                                            alt="{{ $deployed->code }}"
                                            class="absolute"
                                            style="width:36pt;opacity:0.2;top:28pt;left:0;"
                                        >
                                    @endif
                                    @if ($seal)
                                        <img
                                            src="data:image/png;base64,{{ $seal }}"
                                            alt="davao-del-sur"
                                            class="absolute"
                                            style="width:36pt;opacity:0.2;top:28pt;right:0;"
                                        >
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="italic font-xs arial" colspan=6 >
                                    Civil Service Form No. 48
                                </td>
                            </tr>
                            <tr>
                                <td class="relative center bahnschrift font-xl bold" colspan=6>
                                    <span class="absolute nowrap" style="top:6pt;left:0;right:0;margin:auto;">
                                        DAILY TIME RECORD
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="relative center font-xs bold" colspan=6>
                                    <span class="absolute" style='font-variant-ligatures:normal;font-variant-caps:normal;orphans:2;widows:2;-webkit-text-stroke-width:0px;text-decoration-thickness:initial;text-decoration-style:initial;text-decoration-color:initial;top:8pt;left:0;right:0;margin:auto;'>
                                        {{-- -----o0o----- --}}
                                    </span>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="underline uppercase courier font-lg center bold" colspan={{ $preview ? 5 : 6 }} style="text-decoration: none;">
                                {{ $employee->name }}
                            </td>
                        </tr>
                        <tr>
                            <td class="courier top center font-xs" colspan={{ $preview ? 5 : 6 }}>
                                Employee
                            </td>
                        </tr>
                        <tr>
                            <td class="arial font-xs bottom right" colspan=2 style="padding-bottom:2.5pt;padding-right:10pt;">
                                For the month of:
                            </td>
                            <td class="underline font-md courier bold center" colspan={{ $preview ? 3 : 4 }} style="text-decoration: none;">
                                {{ $label }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-xs left middle arial" colspan=2 rowspan=2 height=40>Official hours for <br> arrival &amp; departure </td>
                            <td class="relative arial font-xs bottom left nowrap" colspan=1>
                                <span class="absolute" style="bottom:1pt;left:-11pt;">
                                    Weekdays
                                </span>
                            </td>
                            <td colspan={{ $preview ? 2 : 3 }} class="underline courier bold font-sm bottom left nowrap" style="text-decoration: none;">
                                as required
                            </td>
                        </tr>
                        <tr>
                            <td class="relative arial font-xs bottom left" colspan=1>
                                <span class="absolute" style="bottom:1pt;left:-11pt;">
                                    Weekends
                                </span>
                            </td>
                            <td colspan={{ $preview ? 2 : 3 }} class="underline courier bold font-sm bottom left nowrap" style="text-decoration: none;">
                                as required
                            </td>
                        </tr>
                        <tr>
                            <td colspan={{ $preview ? 5 : 6 }}></td>
                        </tr>
                        <tr class="font-sm bold">
                            <td class="border center middle courier" rowspan=2 height=42 width=58>DAY</td>
                            <td class="border center middle courier" colspan=2 width=116>AM</td>
                            <td class="border center middle courier" colspan=2 width=116>PM</td>
                            @if (! $preview)
                                <td class="border center middle courier" rowspan=2 width=58>Under<br>time</td>
                            @endif
                        </tr>
                        <tr class="font-sm bold">
                            <td class="border courier center" width=58 style="font-size:7.5pt;">Arrival</td>
                            <td class="border courier center" width=58 style="font-size:7.5pt;">Departure</td>
                            <td class="border courier center" width=58 style="font-size:7.5pt;">Arrival</td>
                            <td class="border courier center" width=58 style="font-size:7.5pt;">Departure</td>
                        </tr>
                        @php($date = $month->clone()->subDay())

                        @php($timelogs = $raw->extract($employee->timelogs, $date))

                        @php($p = $hasPreviousDay($raw->extract($employee->timelogs, $date->clone()->addDay()), $timelogs))
                        @if ($p ?: !$preview)
                            <tr class="font-sm">
                                <td class="border courier right bold" style="padding-right:14pt;padding-top:1pt;opacity:0.5;">
                                    @if ($from === 1 && $period !== 'dates' && $p)
                                        <small style="font-size:6pt;">
                                            {{ $date->format('M') }}
                                        </small>
                                        {{ $date->day }}
                                    @else
                                        --
                                    @endif
                                </td>

                                @if ($from === 1 && $period !== 'dates')
                                    @if ($p)
                                        @foreach (['p1', 'p2', 'p3', 'p4'] as $punch)
                                            <td
                                                width=58
                                                @class([
                                                    'relative border nowrap',
                                                    'courier' => !$preview,
                                                    'font-mono' => $preview,
                                                    'invalid' => @$timelogs[$punch] === null && (@$misc['highlights'] ?? false),
                                                ])
                                                @style([
                                                    'padding-top:1pt;opacity:0.5;',
                                                    $preview ? 'padding-right:5pt' : 'padding-left:5pt',
                                                    'background-color:' . (@$timelogs[$punch]['background'] ?? 'transparent'),
                                                    'text-color:' . (@$timelogs[$punch]['foreground'] ?? 'black'),
                                                ])
                                            >
                                                @if (@$timelogs[$punch]['recast'])
                                                    <sup @style([
                                                        'font-size:6pt',
                                                        'position:absolute',
                                                        'top:2pt',
                                                        'left:2pt',
                                                    ])>
                                                        ‽
                                                    </sup>
                                                @endif
                                                {{ substr($timelogs[$punch]['time'] ?? '', 0, strrpos($timelogs[$punch]['time'] ?? '', ":")) }}
                                            </td>
                                        @endforeach
                                        <td class="border"></td>
                                    @else
                                        <td class="border" colspan="5"></td>
                                    @endif
                                @else
                                    <td class="border" colspan="5"></td>
                                @endif
                            </tr>
                        @endif
                        @for ($day = 1; $day <= $month->daysInMonth; $day++)
                            @php($date = $month->clone()->setDay($day))

                            @php($timelogs = $raw->extract($employee->timelogs, $date))

                            @php($holiday = Holiday::search($date, false))

                            @php($p = ($day === $from - 1) && $hasPreviousDay($raw->extract($employee->timelogs, $date->clone()->addDay()), $timelogs))

                            @php($n = ($day === $to + 1 && $date->day === $day) && $hasNextDay($raw->extract($employee->timelogs, $date->clone()->subDay()), $timelogs))

                            @if (
                                ($from <= $day && $day <= $to) ||
                                ($p && $day === $from - 1 || $n && $day === $to + 1 && $date->day === $day) ||
                                ($period === 'dates' && in_array($date->format('Y-m-d'), $dates)) ||
                                (($date->isWeekend() || $holiday) && $from <= $day && $day <= $to)
                            )
                                <tr
                                    @class([
                                        'weekend' => $date->isWeekend() && (@$misc['weekends'] ?? true) && ($from <= $day && $day <= $to),
                                        'holiday' => $holiday && (@$misc['holidays'] ?? true) && ($from <= $day && $day <= $to),
                                        'absent' => array_filter($timelogs) == false && (@$misc['highlights'] ?? false) && ($from <= $day && $day <= $to),
                                        'font-sm' => true
                                    ])
                                >
                                    <td
                                        @class([
                                            'border right bold',
                                            $preview ? 'font-mono' : 'courier',
                                        ])
                                        @style([
                                            'padding-right:14pt;padding-top:1pt;',
                                            'opacity:0.5' => ! ($from <= $day && $day <= $to),
                                        ])
                                    >
                                        {{ $day }}
                                    </td>

                                    @if (array_filter($timelogs))
                                        @if (($from <= $day && $day <= $to) || $p || $n)
                                            @foreach (['p1', 'p2', 'p3', 'p4'] as $punch)
                                                <td
                                                    width=58
                                                    @class([
                                                        'relative border nowrap',
                                                        'courier' => !$preview,
                                                        'font-mono' => $preview,
                                                        'invalid' => @$timelogs[$punch] === null && (@$misc['highlights'] ?? false),
                                                    ])
                                                    @style([
                                                        'padding-top:1pt',
                                                        $preview ? 'padding-right:5pt' : 'padding-left:5pt',
                                                        'background-color:' . (@$timelogs[$punch]['background'] ?? 'transparent'),
                                                        'text-color:' . (@$timelogs[$punch]['foreground'] ?? 'black'),
                                                        'opacity:0.5' => ! ($from <= $day && $day <= $to),
                                                    ])
                                                >
                                                    @if (@$timelogs[$punch]['recast'])
                                                        <sup @style([
                                                            'font-size:6pt',
                                                            'position:absolute',
                                                            'top:2pt',
                                                            'left:2pt',
                                                        ])>
                                                            ‽
                                                        </sup>
                                                    @endif
                                                    {{ substr($timelogs[$punch]['time'] ?? '', 0, strrpos($timelogs[$punch]['time'] ?? '', ":")) }}
                                                </td>
                                            @endforeach
                                            <td class="border"></td>
                                        @elseif($day === $from -1 || $day === $to + 1)
                                            <td class="border" colspan="5"></td>
                                        @else
                                            <td class="border"></td>
                                            <td class="border"></td>
                                            <td class="border"></td>
                                            <td class="border"></td>
                                            <td class="border"></td>
                                        @endif
                                    @elseif($from <= $day && $day <= $to && ($date->isWeekend() && @($misc['weekends'] ?? true) || $holiday && (@$misc['holidays'] ?? true)))
                                        <td
                                            colspan=4
                                            @class([
                                                'border cascadia nowrap',
                                                 $preview ? 'text-left px-4' : 'center'
                                            ])
                                            @style([
                                                'overflow:hidden;text-overflow:ellipsis;',
                                                'opacity:0.5' => ! ($from <= $day && $day <= $to),
                                            ])
                                        >
                                            {{ $holiday?->name ?? $date->format('l') }}
                                        </td>
                                        <td class="border"></td>
                                    @elseif(!($from <= $day && $day <= $to))
                                        <td class="border" colspan="4"></td>
                                        <td class="border"></td>
                                    @else
                                        <td class="border"></td>
                                        <td class="border"></td>
                                        <td class="border"></td>
                                        <td class="border"></td>
                                        <td class="border"></td>
                                    @endif
                                </tr>
                            @elseif(!$preview)
                                <tr>
                                    <td class="border right courier bold font-sm"
                                        style="padding-right:14pt;padding-top:1pt;opacity:0.5;"
                                    >
                                        &nbsp; {{  $day === $date->day ? $day : '--' }}
                                    </td>
                                    <td class="border courier" colspan={{ $preview ? 6 : 5 }} style="padding-left:11pt;">

                                    </td>
                                </tr>
                            @endif
                        @endfor
                        @php($date = $month->clone()->addMonth()->startOfMonth())

                        @php($timelogs = $raw->extract($employee->timelogs, $date))

                        @php(
                            $n = $hasNextDay($raw->extract($employee->timelogs, $date->clone()->subDay()), $timelogs) ||
                                $hasNextDay($raw->extract($employee->timelogs, $date->clone()->subDay(2)), $timelogs, $raw->extract($employee->timelogs, $date->clone()->subDay(2)))
                        )

                        @if ($n ?: !$preview)
                            <tr class="font-sm">
                                <td class="border courier right bold" style="padding-right:14pt;padding-top:1pt;opacity:0.5;">
                                    @if ($n && $to === $date->clone()->subMonth()->daysInMonth && $period !== 'dates')
                                        <small style="font-size:6pt;">
                                            {{ $date->format('M') }}
                                        </small>
                                        {{ $date->day }}
                                    @else
                                        --
                                    @endif
                                </td>

                                @if ($n && $to === $date->clone()->subMonth()->daysInMonth && $period !== 'dates')
                                    @if ($n)
                                        @foreach (['p1', 'p2', 'p3', 'p4'] as $punch)
                                            <td
                                                width=58
                                                @class([
                                                    'relative border nowrap',
                                                    'courier' => !$preview,
                                                    'font-mono' => $preview,
                                                    'invalid' => @$timelogs[$punch] === null && (@$misc['highlights'] ?? false),
                                                ])
                                                @style([
                                                    'padding-top:1pt;opacity:0.5;',
                                                    $preview ? 'padding-right:5pt' : 'padding-left:5pt',
                                                    'background-color:' . (@$timelogs[$punch]['background'] ?? 'transparent'),
                                                    'text-color:' . (@$timelogs[$punch]['foreground'] ?? 'black'),
                                                ])
                                            >
                                                @if (@$timelogs[$punch]['recast'])
                                                    <sup @style([
                                                        'font-size:6pt',
                                                        'position:absolute',
                                                        'top:2pt',
                                                        'left:2pt',
                                                    ])>
                                                        ‽
                                                    </sup>
                                                @endif
                                                {{ substr($timelogs[$punch]['time'] ?? '', 0, strrpos($timelogs[$punch]['time'] ?? '', ":")) }}
                                            </td>
                                        @endforeach
                                        <td class="border"></td>
                                    @else
                                        <td class="border" colspan="5"></td>
                                    @endif
                                @else
                                    <td class="border" colspan="5"></td>
                                @endif
                            </tr>
                        @endif
                        @if (! $preview)
                            @for ($i = 0; $i < 31 - $month->daysInMonth; $i++)
                                <tr class="font-sm">
                                    <td class="border courier right bold" style="padding-right:14pt;padding-top:1pt;opacity:0.5;">
                                        --
                                    </td>
                                    <td class="border" colspan="5"></td>
                                </tr>
                            @endfor
                            @if ($size === 'legal')
                                <tr>
                                    <td colspan=6></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan=2 class="font-md courier right bold" style="padding-right:10pt;padding-bottom:2pt;">TOTAL:</td>
                                <td colspan=4 @class(["underline courier left", 'font-md bold' ])>

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
                            @if (! ($supervisor ??= @$misc['supervisor'] ?? true))
                                <tr>
                                    <td colspan=6 style="height:22.5pt;"></td>
                                </tr>
                            @endif
                            <tr>
                                <td class="underline" colspan=6></td>
                            </tr>
                            <tr>
                                <td class="bahnschrift-light top center font-xs" colspan=6>Employee Signature</td>
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
                            @if($supervisor)
                                <tr>
                                    <td colspan=6 @class(['center font-sm', 'underline' => $supervisor ??= true])>
                                        @if($supervisor)
                                            {{ ($sv = $employee->currentDeployment?->supervisor?->titled_name) === $employee->titled_name ? null : $sv }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="bahnschrift-light top center font-xs" colspan=6>
                                        Immediate Supervisor
                                    </td>
                                </tr>
                            @endif
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
                                    {{ ($head = $employee->currentDeployment?->office?->head)?->is($employee) ? null : $head?->titled_name }}
                                </td>
                            </tr>
                            <tr>
                                <td class="bahnschrift-light top center font-xs" colspan=6>Office Head</td>
                            </tr>
                            <tr>
                                <td colspan=1 class="relative">
                                    <div class="consolas" style="font-size:4.0pt;opacity:0.5;">
                                        ‽ = Rectified
                                    </div>
                                    <div class="absolute font-xxs consolas" style="opacity:0.3;transform:rotate(270deg);left:-17pt;top:10pt;">

                                    </div>
                                    <div class="absolute consolas" style="opacity:0.3;font-size:5pt;top:0pt;">

                                    </div>
                                </td>
                                <td colspan=2></td>
                                <td colspan=3 class="relative">
                                    @if($logo)
                                        <img
                                            src="data:image/png;base64,{{ $logo }}"
                                            alt="{{ $office->code }}"
                                            class="absolute"
                                            style="width:36pt;height:auto;opacity:0.15;margin:auto;top:5pt;left:0;right:0;"
                                        >
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan=3></td>
                                <td
                                    colspan=3
                                    @class([
                                        'relative font-xs center bottom bold courier nowrap',
                                        'underline' => @$misc['officer'] ?? true
                                    ])
                                    @style([
                                        'color:#0007;border-color:#0007!important;' => @$misc['officer'] ?? true
                                    ])
                                >
                                </td>
                            </tr>
                            <tr>
                                <td colspan=3> </td>
                                <td class="relative font-xxs center courier top nowrap" colspan=3 style="color:#0007;">
                                    @if (@$misc['officer'] ?? true)
                                        {{ $user->position ?: $user?->employee?->designation ?? 'Officer-in-charge' }}
                                    @endif

                                    <div class="absolute consolas" style="opacity:0.8;bottom:-1pt;right:0;font-size:4.0pt;">
                                        {{ $time->format('Y-m-d|H:i') }}
                                    </div>
                                </td>
                            </tr>
                            @if (strlen($label) <= 27)
                                <tr>
                                    <td colspan=6></td>
                                </tr>
                            @endif
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
