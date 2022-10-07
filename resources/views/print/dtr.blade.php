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
            @foreach ($employees->load('shift') as $employee)
                <div class="pagebreak"></div>
                <table border=0 cellpadding=0 cellspacing=0 width=732 style='border-collapse:collapse;table-layout:fixed;width:548pt'>
                    <tr height=19>
                        <td colspan=8 class="midline"></td>
                        <td colspan=8></td>
                    </tr>
                    <tr height=19 class="arial">
                        <td colspan=7 class="italic font-xs">Civil Service Form No. 48</td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class="italic font-xs">Civil Service Form No. 48</td>
                    </tr>
                    <tr height=19 class="arial">
                        <td colspan=8 class="midline"></td>
                        <td colspan=8></td>
                    </tr>
                    <tr height=21>
                        <td colspan=7 class="bold center font-lg arial">DAILY TIME RECORD</td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class="bold center font-lg arial">DAILY TIME RECORD</td>
                    </tr>
                    <tr height=19>
                        <td colspan=7 class="font-xs center bold">
                            -----o0o-----
                        </td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class="font-xs center bold">
                            -----o0o-----
                        </td>
                    </tr>
                    <tr height=19>
                        <td colspan=8 class="midline"></td>
                        <td colspan=8></td>
                    </tr>
                    <tr height=20>
                        <td colspan=7 class="center bold consolas font-md"> {{ $employee->name_format->fullStartLastInitialMiddle }} </td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class="center bold consolas font-md">{{ $employee->name_format->fullStartLastInitialMiddle }}</td>
                    </tr>
                    <tr height=19>
                        <td colspan=7 class="font-xs arial center overline">(Name)</td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class="font-xs arial center overline">(Name)</td>
                    </tr>
                    <tr height=19>
                        <td colspan=2 class="font-xs arial bottom center">For the month of:</td>
                        <td colspan=5 class="underline consolas bottom font-sm bold">{{ "{$from->format('F d')}–{$to->format('d Y')}" }}</td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=2 class="font-xs arial bottom center">For the month of:</td>
                        <td colspan=5 class="underline consolas bottom font-sm bold">{{ "{$from->format('F d')}–{$to->format('d Y')}" }}</td>
                    </tr>
                    <tr height=19>
                        <td colspan=3 rowspan=2 class="font-xs middle arial right" width=131 style='width:98pt;padding-right:9pt;'>Official hours for <br> arrival and departure </td>
                        <td colspan=2 class="bottom arial font-xs" width=96 style='width:72pt'>Regular Days</td>
                        <td colspan=2 class="underline font-xs consolas bottom">08:00 to 16:00</td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=3 rowspan=2 class="font-xs middle arial right" width=131 style='width:98pt;padding-right:9pt;'>Official hours for <br> arrival and departure </td>
                        <td colspan=2 class="bottom arial font-xs" width=96 style='width:72pt'>Regular Days</td>
                        <td colspan=2 class="underline font-xs consolas bottom">08:00 to 16:00</td>
                    </tr>
                    <tr height=19>
                        <td colspan=2 class="bottom arial font-xs" width=96 style='width:72pt'>Saturdays</td>
                        <td colspan=2 class="underline font-xs consolas bottom">as required</td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=2 class="bottom arial font-xs" width=96 style='width:72pt'>Saturdays</td>
                        <td colspan=2 class="underline font-xs consolas bottom">as required</td>
                    </tr>
                    <tr style='line-height:3.0pt'>
                        <td colspan=7 class=xl8716797></td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class=xl8716797></td>
                    </tr>
                    <tr height=21 style='height:15.75pt'>
                        <td rowspan=2 class="border font-md cascadia center middle">DAY</td>
                        <td colspan=2 class="border font-md cascadia center middle">AM</td>
                        <td colspan=2 class="border font-md cascadia center middle">PM</td>
                        <td colspan=2 class="border font-md cascadia center middle">Undertime</td>
                        <td class="midline"></td>
                        <td></td>
                        <td rowspan=2 class="border font-md cascadia center middle">DAY</td>
                        <td colspan=2 class="border font-md cascadia center middle">AM</td>
                        <td colspan=2 class="border font-md cascadia center middle">PM</td>
                        <td colspan=2 class="border font-md cascadia center middle">Undertime</td>
                    </tr>
                    <tr height=21 style='height:15.75pt'>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Arr.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Dept.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Arr.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Dept.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Hr.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Min.</td>
                        <td class="midline"></td>
                        <td></td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Arr.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Dept.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Arr.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Dept.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Hrs.</td>
                        <td class="border font-md cascadia center middle" style='border-top:none;border-left:none'>Mins.</td>
                    </tr>
                    @for ($day = 1; $day <= 31; $day++)

                        @php($date = $month->clone()->setDay($day))

                        @php(['in1' => $in1, 'in2' => $in2, 'out1' => $out1, 'out2' => $out2] = $timelog->logsForTheDay($employee, $month->clone()->setDay($day)))

                        <tr height=21 style='height:15.75pt' @class(["weekend" => $date->isWeekend()])>
                            @for($side = 1; $side <= 2; $side++)
                                <td class="border font-md courier bold center middle" style='border-top:none'>{{ $day }}</td>

                                {{-- IN1 --}}
                                <td class="border consolas font-sm" style='border-top:none;border-left:none;'>
                                    <div class="font-sm nowrap consolas {{ $in1?->scanner->name }}" style="margin: 0 auto;">
                                        {{ $in1?->time->format('H:i') }}
                                    </div>
                                </td>

                                {{-- OUT1 --}}
                                <td class="border consolas font-sm" style='border-top:none;border-left:none'>
                                    <div class="font-sm nowrap consolas {{ $out1?->scanner->name }}" style="margin: 0 auto;">
                                        {{ $out1?->time->format('H:i') }}
                                    </div>
                                </td>

                                {{-- IN2 --}}
                                <td class="border consolas font-sm" style='border-top:none;border-left:none'>
                                    <div class="font-sm nowrap consolas {{ $in2?->scanner->name }}" style="margin: 0 auto;">
                                        {{ $in2?->time->format('H:i') }}
                                    </div>
                                </td>

                                {{-- OUT2 --}}
                                <td class="border consolas font-sm" style='border-top:none;border-left:none'>
                                    <div class="font-sm nowrap consolas {{ $out2?->scanner->name }}" style="margin: 0 auto;">
                                        {{ $out2?->time->format('H:i') }}
                                    </div>
                                </td>

                                {{-- UNDERTIME HOURS --}}
                                <td class="border consolas font-sm" style='border-top:none;border-left:none'>

                                </td>

                                {{-- UNDERTIME MINUTES --}}
                                <td class="border consolas font-sm" style='border-top:none;border-left:none'>

                                </td>

                                @if($side == 1)
                                    <td class="midline" style="background-color:#ffff;"></td>
                                    <td style="background-color:#ffff;border-left:none;"></td>
                                @endif
                            @endfor
                        </tr>
                    @endfor
                    <tr height=20 style='height:20.0pt'>
                        <td colspan=2 class="bold font-md consolas bottom right">TOTAL:</td>
                        <td colspan=5 class="underline"></td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=2 class="bold font-md consolas bottom right">TOTAL:</td>
                        <td colspan=5 class="underline"></td>
                    </tr>
                    <tr height=19>
                        <td colspan=7 rowspan=3 class="italic wrap font-xs arial" width=323 style='width:242pt'>
                            I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                        </td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 rowspan=3 class="italic wrap font-xs arial" width=323 style='width:242pt'>
                            I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                        </td>
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
                        <td colspan=7 class=xl8716797></td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class=xl8716797></td>
                    </tr>
                    <tr height=19>
                        <td colspan=7 class="overline"></td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class="overline"></td>
                    </tr>
                    <tr height=19>
                        <td colspan=7 class="italic font-sm arial">Verified as to the prescribed office hours:</td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class="italic font-sm arial">Verified as to the prescribed office hours:</td>
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
                        <td colspan=7 class=xl8716797></td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=7 class=xl8716797></td>
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
                        <td colspan=3 style="border-right: none!important;"></td>
                        <td colspan=4 class="underline uppercase consolas bold font-sm center nowrap">{{ auth()->user()->name }}</td>
                        <td class="midline"></td>
                        <td></td>
                        <td colspan=3 class="midline" style="border-right: none"></td>
                        <td colspan=4 class="underline uppercase consolas bold font-sm center nowrap">{{ auth()->user()->name }}</td>
                    </tr>
                    <tr height=19>
                        <td colspan=3 style="border-right: none"></td>
                        <td colspan=4 class="font-xs center arial" style="border-right: none;">{{ auth()->user()->title }}</td>
                        <td class="midline"></td>
                        <td style='border-left:none;border-bottom:none!important;'></td>
                        <td colspan=3></td>
                        <td colspan=4 class="font-xs center arial">{{ auth()->user()->title }}</td>
                    </tr>
                </table>
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
