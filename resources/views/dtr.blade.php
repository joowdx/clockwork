<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
        <style>
            @page {
                margin: 10mm;
                scale: 100%;
            }

            @media print {
                .pagebreak {
                    page-break-before: always;
                }
                .pagebreak:not(:first-child) {
                    page-break-after: always;
                }
            }

            .weekend {
                background: #33333322;
                border-color: transparent !important;
            }

            .xl6920299 {
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                text-transform: uppercase;
            }

            .xl6820299 {
                font-size: 11.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: "Cascadia Code", monospace;
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: .5pt solid windowtext;
                border-left: none;
                white-space: nowrap;
            }

            .xl7120299 {
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Bahnschrift, sans-serif;
                text-align: center;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: .5pt solid windowtext;
                border-left: none;
                white-space: nowrap;
            }

            .xl7220299 {
                font-size: 10.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                text-align: left;
                vertical-align: center;
                white-space: nowrap;
            }

            .xl7320299 {
                font-size: 10.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                text-align: left;
                vertical-align: center;
                white-space: nowrap;
            }

            .xl7520299 {
                font-size: 8.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                text-align: left;
                vertical-align: bottom;
                white-space: nowrap;
            }

            .xl7620299 {
                font-size: 20.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: Bahnschrift, sans-serif;
                text-align: center;
                vertical-align: middle;
                white-space: nowrap;
            }

            .xl7720299 {
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Bahnschrift, sans-serif;
                text-align: left;
                vertical-align: bottom;
                white-space: nowrap;
                text-transform: uppercase;
            }

            .xl7920299 {
                font-size: 11.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: Bahnschrift, sans-serif;
                text-align: general;
                vertical-align: bottom;
                white-space: nowrap;
            }

            .xl8020299 {
                padding-left: 12px;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                text-align: left;
                vertical-align: center;
                white-space: nowrap;
                text-transform: uppercase;
            }

            .xl8120299 {
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: "Bahnschrift Light", sans-serif;
                text-align: center;
                vertical-align: bottom;
                border-top: .5pt solid windowtext;
                border-right: none;
                border-bottom: none;
                border-left: none;
                white-space: nowrap;
            }
        </style>
        <title>DAILY TIME RECORD</title>
    </head>
    <body>
        <div align="center">
            @foreach ($employees as $employee)
                @for ($x = 0; $x < $pages = ceil(Carbon\CarbonPeriod::create($from, $to)->count() / 31); $x++)
                    <div class="pagebreak"></div>
                    <table border="0" cellpadding="0" cellspacing="0" width="640" style="border-collapse:collapse;table-layout:fixed;width:480pt">
                        <tbody>
                            <tr height="20" style="height:15.0pt">
                                <td colspan="10" rowspan="2" height="40" class="xl7620299" width="640" style="height:30.0pt;width:480pt">DAILY TIME RECORD</td>
                            </tr>
                            <tr height="20" style="height:15.0pt"></tr>
                            <tr height="20" style="height:15.0pt"></tr>
                            <tr height="20" style="height:15.0pt">
                                <td colspan="2" height="20" class="xl7920299" style="height:15.0pt">EMPLOYEE</td>
                                <td colspan="8" class="xl7720299">
                                    {{ $employee->name_format->fullStartLastInitialMiddle }}
                                </td>
                            </tr>
                            <tr height="20" style="height:15.0pt"></tr>
                            <tr height="20" style="height:15.0pt">
                                <td colspan="2" height="20" class="xl7920299" style="height:15.0pt">FROM</td>
                                <td colspan="8" class="xl7720299">
                                    {{ $from->format('d M Y') }}
                                </td>
                            </tr>
                            <tr height="20" style="height:15.0pt">
                                <td colspan="2" height="20" class="xl7920299" style="height:15.0pt">TO</td>
                                <td colspan="8" class="xl7720299">
                                    {{ $to->format('d M Y') }}
                                </td>
                            </tr>
                            <tr height="20" style="height:15.0pt"></tr>
                            <tr height="22" style="height:16.5pt">
                                <td colspan="2" height="22" class="xl6820299" style="height:16.5pt">DATE</td>
                                <td class="xl6820299">IN</td>
                                <td class="xl6820299">OUT</td>
                                <td class="xl6820299">IN</td>
                                <td class="xl6820299">OUT</td>
                                <td class="xl6820299">IN</td>
                                <td class="xl6820299">OUT</td>
                                <td class="xl6820299">IN</td>
                                <td class="xl6820299">OUT</td>
                            </tr>
                            @foreach ($days = (Carbon\CarbonPeriod::create($from->clone()->addDays($x * 31), $x < $pages && ($end = $from->clone()->addDays(($x + 1) * 31))->lt($to) ? $end->subDay() : $to)) as $date)
                                <tr height="20" @if ($date->isWeekend()) class="weekend" @endif style="height:15.0pt">
                                    <td colspan="2" height="20" class="xl7220299" style="height:15.0pt">
                                        {{  $date->format('D d-m-y') }}
                                    </td>
                                    @php $i = 0 @endphp
                                    @foreach ($employee->logsForTheDay($date) as $key => $log)
                                        @if ($log->in && $i % 2 == 0 || $log->out && $i % 2 == 1)
                                            @php $i++ @endphp
                                        @else
                                            <td class="xl7320299"></td>
                                            @php $i+=2 @endphp
                                        @endif
                                        <td class="xl7320299" style="background-color:{{$log->scanner->print_background_colour }};color:{{$log->scanner->print_text_colour}};padding-left:12px;border-right:solid;border-color:white"> {{ $log->time->format('H:i') }} </td>
                                    @endforeach
                                    @if (8 - $i > 0)
                                        <td colspan="{{ 8 - $i }}" class="xl7320299"></td>
                                    @endif
                                </tr>
                                @if ($loop->last)
                                    <td class="xl6820299" colspan="10"></td>
                                @endif
                            @endforeach
                            @for ($i = 31 - $days->count() + 1; $i > 0; $i--)
                                <tr height="20" style="height:15.0pt"> </tr>
                            @endfor
                            <tr height="20" style="height:15.0pt">
                                <td class="xl6820299" style="border-bottom: none">
                                    SCANNERS
                                </td>
                            </tr>
                            @foreach ($employee->scanners->chunk(5) as $scanners)
                                <tr height="20" style="height:15.0pt">
                                    @foreach ($scanners as $scanner)
                                        <td colspan="2" class="xl8020299" style="background-color:{{$scanner->print_background_colour }};color:{{$scanner->print_text_colour}};border-right:solid;border-color:white">
                                            {{ $scanner->name }} ({{ str_pad($scanner->pivot->uid, 4, 0, STR_PAD_LEFT) }})
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr height="20" style="height:15.0pt"></tr>
                            <tr height="20" style="height:15.0pt"></tr>
                            <tr height="20" style="height:15.0pt"></tr>
                            <tr height="20" style="height:15.0pt"></tr>
                            <tr height="20" style="height:15.0pt">
                                <td colspan="6"></td>
                                <td colspan="4" class="xl7120299">
                                    {{ auth()->user()?->name }}
                                </td>
                            </tr>
                            <tr height="20" style="height:15.0pt">
                                <td colspan="3" height="20" class="xl7520299" style="height:15.0pt">
                                    DATE PRINTED: <font class="xl6920299">{{ today()->format('d M Y') }}</font>
                                </td>
                                <td colspan="3"></td>
                                <td colspan="4" class="xl8120299">
                                    {{ auth()->user()?->title }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                @endfor
            @endforeach
        </div>
    </body>
</html>
