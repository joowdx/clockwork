<?php
use App\Enums\TimelogMode;
?>

@extends('print.layout')

@php($size ??= 'folio')

@php($preview ??= false)

@section('content')
    @foreach ($employees as $employee)
        <div
            @class(['pagebreak', 'pb-8 border-b' => $preview && ! $loop->last, 'pt-8' => ! $loop->first])
            style="display:flex;align-items:center;justify-content:center;max-width:620pt;margin:auto;"
        >
            <table
                border="0"
                cellpadding="0"
                cellspacing="0"
                @style([
                    'border-collapse:collapse',
                    'table-layout:fixed',
                    'width:fit-content',
                    'overflow:hidden',
                ])
            >
                @if ($preview)
                    <col width=65 span=10>
                @else
                    <col width=73 span=10>
                @endif
                <tbody>
                    @if (!$preview)
                        <tr></tr>
                        <tr>
                            <td colspan="10" class="relative right">
                                <span style="font-size:4.65pt;opacity:0.05;">ᜑᜊᜄᜆᜅ᜔ ᜇᜊᜏ᜔</span>
                                @if (file_exists(storage_path('app/public/'.settings('seal'))))
                                    <img
                                        src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/'.settings('seal')))) }}"
                                        alt="davao-del-sur"
                                        class="absolute"
                                        style="width:36pt;opacity:0.2;top:15pt;right:0;"
                                    >
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="10" class="bold center bahnschrift font-xl">
                                {{ settings('name') }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="10" class="bold center arial font-lg">
                                Employee Attendance
                            </td>
                        </tr>
                        @if ($size !== 'letter')
                            <tr></tr>
                        @endif
                        @if (in_array($size, ['folio', 'legal']))
                            <tr></tr>
                        @endif
                    @endif
                    <tr>
                        <td class="font-md bold bottom bahnschrift left">NAME</td>
                        <td colspan="4" class="uppercase font-md bottom consolas left whitespace-nowrap">
                            {{ $employee->full_name }}
                        </td>
                        <td class="font-md top courier right" colspan="5" rowspan="3">
                            <span class="uppercase bold">Mode</span>

                            <div style="display:flex;flex-wrap:wrap;">
                                <div @class(['lowercase', $preview ? 'font-sm' : 'font-xs']) style="width:50%;">
                                    &nbsp;
                                </div>
                                @foreach (collect(TimelogMode::cases())->unique->getCode() as $mode)
                                    <div @class(['lowercase whitespace-nowrap', $preview ? 'font-sm' : 'font-xs']) style="width:50%;">
                                        {{ $mode->getLabel() }} = <sub>{{ $mode->getCode() }}</sub>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-md bold bottom bahnschrift left">MONTH</td>
                        <td colspan="4" class="uppercase font-md bottom consolas left">
                            {{ $month->format('M Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="font-md bold bottom bahnschrift left">DATES</td>
                        <td colspan="4" class="uppercase font-md bottom consolas left">
                            {{
                                $period === 'dates'
                                    ? (new \App\Helpers\NumberRangeCompressor)
                                        (
                                            collect($dates)
                                                ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('j'))
                                                ->sort()
                                                ->values()
                                                ->toArray()
                                        )
                                    : "$from-$to"
                            }}
                        </td>
                    </tr>
                    @if (in_array($size, ['folio', 'legal']))
                        <tr></tr>
                    @endif
                    <tr height="22" style="height:16.5pt">
                        <td colspan="1" height="22" @class(['underline bold bottom nowrap font-md', $preview ? 'font-mono left' : 'cascadia']) style="height:16.5pt">DAY</td>
                        <td class="underline" colspan="5"></td>
                        <td class="underline font-md top courier right" colspan="4">
                            <span class="uppercase bold">State</span>

                            <div style="display:flex;flex-wrap:wrap;">
                                <div @class(['lowercase', $preview ? 'font-sm' : 'font-xs']) style="width:calc(100%/3);">
                                    unknown = <sup>u</sup>
                                </div>
                                <div @class(['lowercase', $preview ? 'font-sm' : 'font-xs']) style="width:calc(100%/3);">
                                    in = <sup> i </sup>
                                </div>
                                <div @class(['lowercase', $preview ? 'font-sm' : 'font-xs']) style="width:calc(100%/3);">
                                    out = <sup> o </sup>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @foreach ($month->range($month->format('Y-m-') . $month->daysInMonth) as $date)
                        @continue(
                            $period === 'dates'
                                ? ! in_array($date->format('Y-m-d'), $dates)
                                : $date->day < $from || $date->day > $to
                        )

                        <tr @class(['underline', $preview ? 'font-mono' : 'courier']) style="border-color: #8888 !important; text-decoration: none;">
                            <td style="padding:3pt 0;">
                                <span class="bold">
                                    {{ $date->format('d') }}
                                </span>
                                {{ $date->format('D') }}
                            </td>
                            @foreach ($employee->timelogs->filter(fn ($t) => $t->time->isSameDay($date))->sortBy('time')->take(9) as $timelog)
                                <td class="relative text-sm" style="padding:1pt 0 ;">
                                    <span class="font-sm nowrap bold"
                                        @style([
                                            "text-color:{$timelog->scanner->foregroundColor}!important;",
                                            "background-color:{$timelog->scanner->backgroundColor}!important;",
                                            'padding:3pt' => !$preview,
                                            'border-radius:2pt',
                                        ])
                                    >
                                        {{ $timelog->time->format('H:i') }}
                                    </span>

                                    <span class="absolute" @style([$preview ?: 'top:-0.75pt;right:7pt;'])>
                                        <sup>{{ match(true) { $timelog->in => 'i', $timelog->out => 'o', default => 'u' } }}</sup><sub>{{ $timelog->mode->getCode() }}</sub>
                                    </span>
                                </td>
                            @endforeach
                            @for ($placeholder = 0; $placeholder < 9 - $employee->timelogs->filter(fn ($t) => $t->time->isSameDay($date))->count(); $placeholder++)
                                <td class="relative text-sm" style="padding:1pt 0 ;">
                                    <span class="font-sm nowrap bold">
                                        &nbsp;
                                    </span>
                                    <span class="absolute" style="top:-0.75pt;right:7pt;">
                                        <sup>&nbsp;</sup><sub>&nbsp;</sub>
                                    </span>
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                    @if (!$preview)
                        @for ($placeholder = 0; $placeholder < 31 - $month->daysInMonth; $placeholder++)
                            <tr class="underline courier" style="border-color: #8888;">
                                <td style="padding:3pt 0;">
                                    <span class="bold">
                                        xx
                                    </span>
                                    xxx
                                </td>
                                @for ($placeholder = 0; $placeholder < 9; $placeholder++)
                                <td class="relative text-sm" style="padding:1pt 0 ;">
                                    <span class="font-sm nowrap bold">
                                        &nbsp;
                                    </span>
                                    <span class="absolute" style="top:-0.75pt;right:7pt;">
                                        <sup>&nbsp;</sup><sub>&nbsp;</sub>
                                    </span>
                                </td>
                                @endfor
                            </tr>
                        @endfor
                    @endif
                    @if ($preview)
                        <tr></tr>
                    @else
                        @if ($size !== 'letter')
                            <tr></tr>
                        @endif
                        @if (in_array($size, ['folio', 'legal']))
                            <tr></tr>
                        @endif
                    @endif
                    <tr>
                        <td colspan="10" rowspan="3" @class(['top courier', 'left' => $preview])>
                            <span @class(['uppercase bold font-md'])>Scanners</span>
                            <div
                                @style([
                                    "display:flex;flex-wrap:wrap;overflow:hidden;",
                                    "height:160.025pt;" => $size === 'legal',
                                    "height:84pt;" => $size === 'folio',
                                    "height:49pt;" => $size === 'a4',
                                    "height:21pt;" => $size === 'letter',
                                    "height:auto!important;" => $preview,
                                ])
                            >
                                @foreach ($employee->scanners->sortBy('name') as $scanner)
                                    <div class="lowercase font-xs" style="padding:1pt;">
                                        <div
                                            @style([
                                                'padding:2pt 3pt 0',
                                                'border-radius:2pt',
                                                'text-color:' . $scanner->foregroundColor . '!important;',
                                                'background-color:' . $scanner->backgroundColor . '!important;',
                                            ])
                                        >
                                            {{ "{$scanner->name} ({$scanner->pivot->uid})" }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @if (! $preview)
                        <tr></tr>
                        <tr></tr>
                        @if (in_array($size, ['folio', 'legal']))
                            <tr></tr>
                        @endif
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="4" class="relative underline font-sm center bottom nowrap consolas">
                                @includeWhen($signature, 'print.signature', ['signature' => $user->signature, 'signed' => $signed ?? false])
                                {{ $user?->name }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="font-xs nowrap consolas">
                                DATE: <span class="uppercase">{{ now()->format('d M Y H:i') }}</span>
                            </td>
                            <td colspan="3"></td>
                            <td colspan="4" class="center nowrap font-xs arial top">
                                {{ $user?->employee?->designation ?? '' }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
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
                        'folio' => '8.5in 13in',
                        default => $size,
                    }
                }};
            }
        }
    </style>
@endpush
