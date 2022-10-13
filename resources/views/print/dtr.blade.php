@inject('timelog', 'App\Services\TimeLogService')
<html lang="en">
    <head>
        <title>DAILY TIME RECORD</title>
        <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
        <link rel="stylesheet" href="{{ asset('css/print.css') }}">
        <style>
            html, body {
                margin: 0;
            }

            @media print {
                @page {
                    margin: 0;
                    size: 8.5in 13in;
                }
            }
        </style>
    </head>
    <body>
        <div align=center>
            @foreach ($employees as $employee)
                @if($csc_format || $employee->csc_format)
                    <div class="pagebreak"></div>
                    <table border=0 cellpadding=0 cellspacing=0 width=732 style='border-collapse:collapse;table-layout:fixed;width:548pt'>
                        <tr height=19>
                            <td colspan=8 class="midline"></td>
                            <td colspan=8></td>
                        </tr>
                        <tr height=19 class="arial">
                            @for ($side = 1; $side <=2; $side++)
                                <td colspan=7 class="italic font-xs">Civil Service Form No. 48</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19 class="arial">
                            <td colspan=8 class="midline"></td>
                            <td colspan=8></td>
                        </tr>
                        <tr height=21>
                            @for ($side = 1; $side <=2; $side++)
                                <td colspan=7 class="bold center font-lg arial">DAILY TIME RECORD</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19>
                            @for ($side = 1; $side <=2; $side++)
                                <td colspan=7 class="font-xs center bold">
                                    -----o0o-----
                                </td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19>
                            <td colspan=8 class="midline"></td>
                            <td colspan=8></td>
                        </tr>
                        <tr height=20>
                            @for ($side = 1; $side <=2; $side++)
                                <td colspan=7 class="center bold consolas font-md">{{ $employee->name_format->fullStartLastInitialMiddle }}</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19>
                            <td colspan=7 class="font-xs arial center overline">(Name)</td>
                            <td class="midline"></td>
                            <td></td>
                            <td colspan=7 class="font-xs arial center overline">(Name)</td>
                        </tr>
                        <tr height=19>
                            @for ($side = 1; $side <=2; $side++)
                                <td colspan=2 class="font-xs arial bottom center">For the month of:</td>
                                <td colspan=5 class="underline consolas bottom font-sm bold">{{ "{$from->format('d')}–{$to->format('d F Y')}" }}</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19>
                            @for ($side = 1; $side <=2; $side++)
                                <td colspan=3 rowspan=2 class="font-xs middle arial right" width=131 style='width:98pt;padding-right:9pt;'>Official hours for <br> arrival and departure </td>
                                <td colspan=2 class="bottom arial font-xs" width=96 style='width:72pt'>Regular Days</td>
                                <td colspan=2 class="underline font-xs consolas bottom">08:00 to 16:00</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19>
                            @for ($side = 1; $side <=2; $side++)
                                <td colspan=2 class="bottom arial font-xs" width=96 style='width:72pt'>Saturdays</td>
                                <td colspan=2 class="underline font-xs consolas bottom">as required</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr style='line-height:3.0pt'>
                            <td colspan=7></td>
                            <td class="midline"></td>
                            <td></td>
                            <td colspan=7></td>
                        </tr>
                        <tr height=21 style='height:15.75pt'>
                            @for ($side = 1; $side <=2; $side++)
                                <td rowspan=2 class="border font-md cascadia center middle">DAY</td>
                                <td colspan=2 class="border font-md cascadia center middle">AM</td>
                                <td colspan=2 class="border font-md cascadia center middle">PM</td>
                                <td colspan=2 class="border font-md cascadia center middle">Undertime</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=21 style='height:15.75pt'>
                            @for ($side = 1; $side <=2; $side++)
                                <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Arr.</td>
                                <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Dept.</td>
                                <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Arr.</td>
                                <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Dept.</td>
                                <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Hr.</td>
                                <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Min.</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        @for ($day = 1; $day <= 31; $day++)

                            @php($date = $month->clone()->setDay($day))

                            @php(@['in1' => $in1, 'in2' => $in2, 'out1' => $out1, 'out2' => $out2, 'hours' => $hrs, 'minutes' => $mnts] = $timelog->logsForTheDay($employee, $date))

                            <tr height=21 style='height:15.75pt' @class(["weekend" => $date->format('d') == $day && $date->isWeekend()])>
                                @for($side = 1; $side <= 2; $side++)
                                    <td class="border font-md courier bold center middle" style='border-top:none'>{{ $date->format('d') == $day ? $day : '' }}</td>

                                    {{-- IN1 --}}
                                    <td class="border consolas" style='border-top:none;border-left:none;position:relative;'>
                                        <div class="font-sm nowrap consolas {{ $in1?->scanner->name }}" style="margin: 0 auto;">
                                            {{ $in1?->time->format('H:i') }}
                                        </div>
                                        {{-- <div style="position:absolute;right:-1.5pt;top:-4.55pt;"> · </div> --}}
                                    </td>

                                    {{-- OUT1 --}}
                                    <td class="border consolas" style='border-top:none;border-left:none;position:relative;'>
                                        <div class="font-sm nowrap consolas {{ $out1?->scanner->name }}" style="margin: 0 auto;">
                                            {{ $out1?->time->format('H:i') }}
                                        </div>
                                        {{-- <div style="position:absolute;right:-1.5pt;top:-4.55pt;"> · </div> --}}
                                    </td>

                                    {{-- IN2 --}}
                                    <td class="border consolas" style='border-top:none;border-left:none;position:relative;'>
                                        <div class="font-sm nowrap consolas {{ $in2?->scanner->name }}" style="margin: 0 auto;">
                                            {{ $in2?->time->format('H:i') }}
                                        </div>
                                        {{-- <div style="position:absolute;right:-1.5pt;top:-4.55pt;"> · </div> --}}
                                    </td>

                                    {{-- OUT2 --}}
                                    <td class="border consolas" style='border-top:none;border-left:none;position:relative;'>
                                        <div class="font-sm nowrap consolas {{ $out2?->scanner->name }}" style="margin: 0 auto;">
                                            {{ $out2?->time->format('H:i') }}
                                        </div>
                                        {{-- <div style="position:absolute;right:-1.5pt;top:-4.55pt;"> · </div> --}}
                                    </td>

                                    {{-- UNDERTIME HOURS --}}
                                    <td class="border consolas font-sm" style='border-top:none;border-left:none;position:relative;'>
                                        <div class="font-sm nowrap consolas right" style="margin: 0 auto; padding-right: 4pt;">
                                            {{ $hrs }}
                                        </div>
                                    </td>

                                    {{-- UNDERTIME MINUTES --}}
                                    <td class="border consolas font-sm" style='border-top:none;border-left:none;position:relative;'>
                                        <div class="font-sm nowrap consolas right" style="margin: 0 auto; padding-right: 4pt;">
                                            {{ $mnts }}
                                        </div>
                                    </td>

                                    @if($side == 1)
                                        <td class="midline" style="background-color:#ffff;"></td>
                                        <td style="background-color:#ffff;border-left:none;"></td>
                                    @endif
                                @endfor
                            </tr>
                        @endfor
                        <tr height=20 style='height:20.0pt'>
                            @for ($side = 1; $side <= 2; $side++)
                                <td colspan=2 class="bold font-md consolas bottom right">TOTAL:</td>
                                <td colspan=5 class="underline"></td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19>
                            @for ($side = 1; $side <= 2; $side++)
                                <td colspan=7 rowspan=3 class="italic wrap font-xs arial" width=323 style='width:242pt'>
                                    I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                                </td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19>
                            <td class="midline"></td>
                            <td></td>
                        </tr>
                        <tr height=19>
                            <td class="midline"></td>
                            <td></td>
                        </tr>
                        <tr height=20>
                            <td colspan=7></td>
                            <td class="midline"></td>
                            <td></td>
                            <td colspan=7></td>
                        </tr>
                        <tr height=20>
                            <td colspan=7></td>
                            <td class="midline"></td>
                            <td></td>
                            <td colspan=7></td>
                        </tr>
                        <tr height=19>
                            <td colspan=7 class="overline"></td>
                            <td class="midline"></td>
                            <td></td>
                            <td colspan=7 class="overline"></td>
                        </tr>
                        <tr height=19>
                            @for ($side = 1; $side <= 2; $side++)
                                <td colspan=7 class="italic font-sm arial">Verified as to the prescribed office hours:</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr style='height:10pt'>
                            <td colspan=8 class="midline"></td>
                            <td colspan=8></td>
                        </tr>
                        <tr height=19>
                            <td colspan=8 class="midline"></td>
                            <td colspan=8></td>
                        </tr>
                        <tr height=20>
                            <td colspan=7></td>
                            <td class="midline"></td>
                            <td></td>
                            <td colspan=7></td>
                        </tr>
                        <tr height=19>
                            <td colspan=7 class="overline"></td>
                            <td class="midline"></td>
                            <td></td>
                            <td colspan=7 class="overline"></td>
                        </tr>
                        <tr height=19>
                            <td colspan=8 class="midline"></td>
                            <td colspan=8></td>
                        </tr>
                        <tr height=20>
                            @for ($side = 1; $side <= 2; $side++)
                                <td colspan=3 style="border-right: none!important;"></td>
                                <td colspan=4 class="underline uppercase consolas bold font-sm center nowrap">{{ auth()->user()->name }}</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                        <tr height=19>
                            @for ($side = 1; $side <= 2; $side++)
                                <td colspan=3 style="border-right: none"></td>
                                <td colspan=4 class="font-xs center arial" style="border-right: none;">{{ auth()->user()->title }}</td>
                                @if ($side == 1)
                                    <td class="midline"></td>
                                    <td></td>
                                @endif
                            @endfor
                        </tr>
                    </table>
                @else
                    @for ($x = 0; $x < $pages = ceil(Carbon\CarbonPeriod::create($from, $to)->count() / 31); $x++)
                        <div class="pagebreak"></div>
                        <table border="0" cellpadding="0" cellspacing="0" width="640">
                            <tbody>
                                <tr height="20">
                                    <td colspan="10" rowspan="2" height="40" class="bold center bahnschrift font-xl" width="640" style="height:30.0pt;width:480pt">DAILY TIME RECORD</td>
                                </tr>
                                <tr height="20"></tr>
                                <tr height="20">
                                    <td height="20" class="font-md bold bottom bahnschrift">NAME</td>
                                    <td colspan="9" class="uppercase font-md bottom consolas">
                                        {{ $employee->nameFormat->fullStartLast }}
                                    </td>
                                </tr>
                                <tr height="20">
                                    <td height="20" class="font-md bold bottom bahnschrift">FROM</td>
                                    <td colspan="9" class="uppercase font-md bottom consolas">
                                        {{ $from->format('D d-M-Y') }}
                                    </td>
                                </tr>
                                <tr height="20">
                                    <td height="20" class="font-md bold bottom bahnschrift">TO</td>
                                    <td colspan="9" class="uppercase font-md bottom consolas">
                                        {{ $to->format('D d-M-Y') }}
                                    </td>
                                </tr>
                                <tr height="22" style="height:16.5pt">
                                    <td colspan="2" height="22" class="underline bold bottom nowrap cascadia font-md" style="height:16.5pt">DATE</td>
                                    <td class="underline bold bottom nowrap cascadia font-md">IN</td>
                                    <td class="underline bold bottom nowrap cascadia font-md">OUT</td>
                                    <td class="underline bold bottom nowrap cascadia font-md">IN</td>
                                    <td class="underline bold bottom nowrap cascadia font-md">OUT</td>
                                    <td class="underline bold bottom nowrap cascadia font-md">IN</td>
                                    <td class="underline bold bottom nowrap cascadia font-md">OUT</td>
                                    <td class="underline bold bottom nowrap cascadia font-md">IN</td>
                                    <td class="underline bold bottom nowrap cascadia font-md">OUT</td>
                                </tr>
                                @foreach ($days = (Carbon\CarbonPeriod::create($from->clone()->addDays($x * 31), $x < $pages && ($end = $from->clone()->addDays(($x + 1) * 31))->lt($to) ? $end->subDay() : $to)) as $date)
                                    <tr height="20" @class(['weekend' => $date->isWeekend(), 'absent' => $employee->absentForTheDay($date) && $date->isWeekDay()])>
                                        <td colspan="2" height="20" @class(['font-sm nowrap consolas',])>
                                            {{  $date->format('D d-m-y') }}
                                        </td>
                                        @php($i = 0)
                                        @foreach ($employee->logsForTheDay($date) as $key => $log)
                                            @if ($log->in && $i % 2 == 0 || $log->out && $i % 2 == 1)
                                                @php($i++)
                                            @else
                                                <td></td>
                                                @php($i+=2)
                                            @endif
                                            <td>
                                                <div class="font-sm nowrap consolas bold {{ $log->scanner->name }}"> {{ $log->time->format('H:i') }} </div>
                                            </td>
                                        @endforeach
                                        @if (8 - $i > 0)
                                            <td colspan="{{ 8 - $i }}"> </td>
                                        @endif
                                    </tr>
                                @endforeach
                                @for ($i = 31 - $days->count() + 1; $i > 0; $i--)
                                    <tr height="20"> </tr>
                                @endfor
                                <tr height="20">
                                    <td class="bold bottom nowrap cascadia font-md" style="border-bottom: none">
                                        SCANNERS
                                    </td>
                                </tr>
                                @foreach ($employee->scanners->chunk(5) as $chunked)
                                    <tr height="20">
                                        @foreach ($chunked as $scanner)
                                            <td colspan="2" class="uppercase font-xs nowrap consolas scanner bold {{ $scanner->name }}">
                                                {{ $scanner->name }} ({{ str_pad($scanner->pivot->uid, 5, 0, STR_PAD_LEFT) }})
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr height="20"></tr>
                                <tr height="20"></tr>
                                <tr height="20"></tr>
                                <tr height="20"></tr>
                                <tr height="20"></tr>
                                <tr height="20">
                                    <td colspan="6"></td>
                                    <td colspan="4" class="underline uppercase bold center bottom nowrap bahnschrift">
                                        {{ auth()->user()?->name }}
                                    </td>
                                </tr>
                                <tr height="20">
                                    <td colspan="3" height="20" class="font-sm nowrap consolas bold">
                                        DATE: <span class="uppercase">{{ now()->format('d M Y H:i') }}</span>
                                    </td>
                                    <td colspan="3"></td>
                                    <td colspan="4" class="center nowrap bahnschrift-light">
                                        {{ auth()->user()?->title }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    @endfor
                @endif
            @endforeach
        </div>
    </body>
    <style>
        @foreach ($employees->flatMap->scanners->unique('name') as $scanner)
            .{{$scanner->name}} {
                background-color: {{$scanner->printBackgroundColour}};
                color: {{$scanner->printTextColour}};
                width: fit-content;
            }
        @endforeach
    </style>
</html>
