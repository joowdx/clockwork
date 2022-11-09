<<<<<<< HEAD
<article style="width:100%;">
    @foreach($months as $month)

        @php($start = $month->isSameMonth($from) ? max($from->clone()->setMonth($month->month), $month->clone()->startOfMonth()) : $month->clone()->startOfMonth())

        @php($end = $month->isSameMonth($to) ? min($to->setMonth($month->month), $month->clone()->endOfMonth()) : $month->clone()->endOfMonth())

        <div class="pagebreak" style="display:flex;align-items:center;justify-content:center;max-width:620pt;margin:auto;">
            @for ($side = 0; $side < 2; $side++)
                <div style="width:100%;border-width:1pt;border-style:@if($side==0)none dashed none none; @else none none none dashed @endif">
                    <table border=0 cellpadding=0 cellspacing=0 style="border-collapse:collapse;table-layout:fixed;width:fit-content;margin:auto!important;">
                        <col width=57 span=6>
                        <tr>
                            <td colspan=6 style="position:relative;">
                                <img src="{{ url('img/davao-del-sur(300x300).png') }}" alt="davao-del-sur" style="position:absolute;width:36pt;opacity:0.1;top:15pt;right:0;">
                            </td>
                        </tr>
                        <tr>
                            <td class="italic font-xs arial" colspan=6 >Civil Service Form No. 48</td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
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
                        <tr>
                            <td class="underline uppercase bold consolas font-lg center" colspan=6>
                                {{ $employee->name_format->fullStartLastInitialMiddle }}
                            </td>
                        </tr>
                        <tr>
                            <td class="consolas top center font-xs" colspan=6>
                                Employee
                            </td>
                        </tr>
                        <tr>
                            <td class="arial font-xs bottom right" colspan=2 style="padding-bottom:2.5pt;padding-right:10pt;">For the month of:</td>
                            <td class="underline font-md consolas center bold" colspan=4>{{ "{$start->format('d')}-{$end->format('d F Y')}" }}</td>
                        </tr>
                        <tr>
                            <td class="font-xs left middle arial" colspan=2 rowspan=2 height=40>Official hours for <br> arrival &amp; departure </td>
                            <td class="arial font-xs bottom left nowrap" colspan=1 style="position:relative;">
                                <span style="position:absolute;bottom:1pt;left:-11pt;">
                                    Regular Days
                                </span>
                            </td>
                            <td class="underline consolas font-sm bold bottom left" colspan=3>{{ $time->weekdays }}</td>
                        </tr>
                        <tr>
                            <td class="arial font-xs bottom left" colspan=1 style="position:relative;">
                                <span style="position:absolute;bottom:1pt;left:-11pt;">
                                    Saturdays
                                </span>
                            </td>
                            <td class="underline consolas font-sm bold bottom left nowrap" colspan=3> {{ $time->weekends }} </td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr class="font-sm bold">
                            <td class="border center middle cascadia" rowspan=2 height=42 width=58>DAY</td>
                            <td class="border center middle cascadia" colspan=2 width=116>AM</td>
                            <td class="border center middle cascadia" colspan=2 width=116>PM</td>
                            <td class="border center middle cascadia" rowspan=2 width=58>Under<br>time</td>
                        </tr>
                        <tr class="font-sm bold">
                            <td class="border cascadia center" width=58 style="font-size:7.5pt;">Arrival</td>
                            <td class="border cascadia center" width=58 style="font-size:7.5pt;">Departure</td>
                            <td class="border cascadia center" width=58 style="font-size:7.5pt;">Arrival</td>
                            <td class="border cascadia center" width=58 style="font-size:7.5pt;">Departure</td>
                        </tr>
                        @php($utTotal = 0)

                        @php($daysTotal = 0)

                        @for ($day = 1; $day <= 31; $day++)
                            @php($date = $month->clone()->setDay($day))

                            @php(@[$in1, $out1, $in2, $out2, $ut] = array_values(@$attlogs[$date->format('Y-m-d')] ?? []))

                            @if ($date->between($start, $end))
                                <tr @class(['weekend' => $weekend = $date->format('d') == $day && $date->isWeekend(), 'invalid' => $ut === null && (! $employee->regular || $date->isWeekday()) && $date->format('d') == $day && $employee->logsForTheDay($date)->isNotEmpty()])>
                                    <td class="border right courier bold" style="padding-right:14pt;">
                                        {{ $date->format('d') == $day ? $day : '' }}
                                    </td>
                                    @if ($weekend && $employee->logsForTheDay($date)->isEmpty())
                                        <td colspan=4 class="border center consolas">
                                            {{ $date->format('l') }}
                                        </td>
                                    @else
                                        <td class="border consolas" width=58 style="position:relative;">
                                            <div class="font-sm nowrap consolas {{ @$in1?->scanner->name }}" style="margin-left:5pt;">
                                                {{ @$in1?->time->format('H:i') }}
                                            </div>
                                            @if (@$ut->in1)
                                                <span class="undertime-badge">
                                                    {{ @$ut->in1 }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="border consolas" width=58 style="position:relative;">
                                            <div class="font-sm nowrap consolas {{ @$out1?->scanner->name }}" style="margin-left:5pt;">
                                                {{ @$out1?->time->format('H:i') }}
                                            </div>
                                            @if (@$ut->out1)
                                                <span class="undertime-badge">
                                                    {{ @$ut->out1 }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="border consolas" width=58 style="position:relative;">
                                            <div class="font-sm nowrap consolas {{ @$in2?->scanner->name }}" style="margin-left:5pt;">
                                                {{ @$in2?->time->format('H:i') }}
                                            </div>
                                            @if (@$ut->in2)
                                                <span class="undertime-badge">
                                                    {{ @$ut->in2 }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="border consolas" width=58 style="position:relative;">
                                            <div class="font-sm nowrap consolas {{ @$out2?->scanner->name }}" style="margin-left:5pt;">
                                                {{ @$out2?->time->format('H:i') }}
                                            </div>
                                            @if (@$ut->out2)
                                                <span class="undertime-badge">
                                                    {{ @$ut->out2 }}
                                                </span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="border consolas" width=58 style="position:relative;">
                                        <div class="font-sm nowrap consolas right" style="margin-left:auto;width:fit-content;padding-right:5pt;">
                                            {{ @$ut?->total ?: '' }}
                                        </div>
                                    </td>
                                </tr>
                                @if (@$calculate)
                                    @php($utTotal += (@$ut?->total ?? 0))

                                    @php($daysTotal += $ut ? 1 : 0)
                                @endif

                            @elseif($date->format('d') == $day)
                                <tr>
                                    <td class="border right courier bold" style="padding-right:14pt;">
                                        {{ $date->format('d') == $day ? $day : '' }}
                                    </td>
                                    <td class="border" colspan=5> </td>
                                </tr>
                            @else
                                <tr>
                                    <td class="border"> </td>
                                    <td class="border" colspan=6> </td>
                                </tr>
                            @endif
                        @endfor
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td colspan=2 class="font-md cascadia right bold" style="padding-right:10pt;padding-bottom:2pt;">TOTAL:</td>
                            @if (@$calculate)
                                <td colspan=2 class="underline consolas font-md left bold"> {{ ($daysTotal ? "{$daysTotal} days " : '') }} </td>
                                <td colspan=2 class="underline consolas font-sm right"> {{ ($utTotal ? "t\u = {$utTotal} mins" : '') }} </td>
                            @else
                                <td colspan=4 class="underline consolas font-md left bold"> </td>
                            @endif
                        </tr>
                        <tr>
                            <td class="italic font-xs arial" colspan=6 rowspan=3>
                                I certify on my honor that the above is a true and correct report of the hours of work performed,
                                record of which was made daily at the time of arrival and departure from office.
                            </td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td class="underline" colspan=6></td>
                        </tr>
                        <tr>
                            <td class="bahnschrift-light top center font-sm" colspan=6>Employee's Signature</td>
                        </tr>
                        <tr>
                            <td class="italic arial font-xs" colspan=6>Verified as to the prescribed office hours:</td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td class="underline" colspan=6></td>
                        </tr>
                        <tr>
                            <td class="bahnschrift-light top center font-sm" colspan=6>Office/Department Head</td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr style="width:100%;border-width:0;border-top-width:0.5pt;border-style:dashed;border-color:#0007!important;">
                            <td colspan=6 style="position:relative;">
                                <img src="{{ url('img/pgo-picto(300x300).png') }}" alt="pgo-picto" style="position:absolute;width:36pt;opacity:0.15;margin:auto;left:0;right:0;">
                            </td>
                        </tr>
                        <tr>
                            <td colspan=1></td>
                            <td class="underline font-xs center bottom bold consolas" colspan=4 style="color:#000A;border-color:#000A!important;">
                                {{ auth()?->user()?->name }}
                            </td>
                            <td colspan=1></td>
                        </tr>
                        <tr>
                            <td colspan=1></td>
                            <td class="font-xxs center arial top" colspan=4 style="color:#000A;">
                                {{ auth()?->user()?->title }}
                            </td>
                            <td colspan=1></td>
                        </tr>
                        <tr style="height:5pt;">
                            <td colspan=1></td>
                            <td class="font-xxs center arial top" colspan=4 style="color:#000A;">
                                {{ auth()?->user()?->title }}
                            </td>
                            <td colspan=1></td>
                        </tr>
                    </table>
                </div>
            @endfor
        </div>
    @endforeach
</article>
=======
@inject('timelog', 'App\Services\TimeLogService')
<html lang="en">
    <head>
        <title>DAILY TIME RECORD</title>
        <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
        <style>
            .xl6516797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: bottom;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl6616797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: general;
                vertical-align: bottom;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl6916797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 9.0pt;
                font-weight: 400;
                font-style: italic;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: middle;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl7016797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: bottom;
                border-top: .5pt solid windowtext;
                border-right: none;
                border-bottom: none;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl7116797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: italic;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: middle;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: normal;
            }

            .xl7216797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 9.0pt;
                font-weight: 400;
                font-style: italic;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl7316797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: general;
                vertical-align: bottom;
                border-top: none;
                border-right: .5pt dashed windowtext;
                border-bottom: none;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl7416797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: general;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: none;
                border-left: .5pt dashed windowtext;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl7516797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: none;
                border-left: .5pt dashed windowtext;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl7616797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: none;
                border-left: .5pt dashed windowtext;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl7716797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: none;
                border-left: .5pt dashed windowtext;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl7816797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: italic;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: middle;
                border-top: none;
                border-right: none;
                border-bottom: none;
                border-left: .5pt dashed windowtext;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: normal;
            }

            .xl7916797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 9.0pt;
                font-weight: 400;
                font-style: italic;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: .5pt dashed windowtext;
                border-bottom: none;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl8016797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 9.0pt;
                font-weight: 400;
                font-style: italic;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: none;
                border-left: .5pt dashed windowtext;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl8116797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: .5pt dashed windowtext;
                border-bottom: none;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl8216797 {
                color: black;
                font-size: 11.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
                padding-left: 18px;
                mso-char-indent-count: 2;
            }

            .xl8416797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: top;
                border-top: .5pt solid windowtext;
                border-right: none;
                border-bottom: none;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl8516797 {
                padding: 1pt;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: bottom;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl8616797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 13.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: bottom;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl8716797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: .5pt solid windowtext;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl8816797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: .5pt solid windowtext;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl8916797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                mso-font-charset: 0;
                mso-number-format: "dd\\-mmm";
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: .5pt solid windowtext;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl9016797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: .5pt solid windowtext;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl9116797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                color: black;
                font-size: 9.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                text-align: center;
                vertical-align: center;
                border: .5pt solid windowtext;
                white-space: nowrap;
            }

            .xl9216797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: right;
                vertical-align: middle;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: normal;
            }

            .xl9316797 {
                color: black;
                font-size: 8.0pt;
                font-weight: 400;
                font-style: normal;
                text-decoration: none;
                font-family: Arial, sans-serif;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: left;
                vertical-align: bottom;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: normal;
                padding-left: 9px;
                mso-char-indent-count: 1;
            }

            .xl9416797 {
                color: black;
                font-size: 9.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: Consolas, monospace;
                mso-font-charset: 0;
                mso-number-format: "\@";
                text-align: left;
                vertical-align: bottom;
                border-top: none;
                border-right: none;
                border-bottom: .5pt solid windowtext;
                border-left: none;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
                mso-char-indent-count: 1;
            }

            .xl9516797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: "Courier New", monospace;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: middle;
                border: .5pt solid windowtext;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }

            .xl9616797 {
                padding-top: 1px;
                padding-right: 1px;
                padding-left: 1px;
                mso-ignore: padding;
                color: black;
                font-size: 11.0pt;
                font-weight: 700;
                font-style: normal;
                text-decoration: none;
                font-family: "Courier New", monospace;
                mso-font-charset: 0;
                mso-number-format: General;
                text-align: center;
                vertical-align: bottom;
                border: .5pt solid windowtext;
                mso-background-source: auto;
                mso-pattern: auto;
                white-space: nowrap;
            }
            body {
                margin: 0;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @media print {
                @page {
                    margin: 0;
                    size: 8.5in 13in;
                }
            }
            .weekend {
                background: #33333322;
                border-color: transparent !important;
            }
        </style>
    </head>
    <body>
        <div align=center>
            @foreach ($employees->load('shift') as $employee)
                <table border=0 cellpadding=0 cellspacing=0 width=732 class=xl6616797 style='border-collapse:collapse;table-layout:fixed;width:548pt'>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=7 class=xl6916797>Civil Service Form No. 48</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7416797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl6916797>Civil Service Form No. 48</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                    <tr height=21 style='mso-height-source:userset;height:15.75pt'>
                        <td colspan=7 class=xl8616797>DAILY TIME RECORD</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7416797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl8616797>DAILY TIME RECORD</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=7 class=xl6516797>
                            -----o0o-----
                        </td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7416797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl6516797>
                            -----o0o-----
                        </td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                    <tr height=20 style='height:15.0pt'>
                        <td colspan=7 class=xl8716797> {{ $employee->name_format->fullStartLastInitialMiddle }} </td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7516797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl8716797>{{ $employee->name_format->fullStartLastInitialMiddle }}</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=7 class=xl8416797>(Name)</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7416797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl8416797>(Name)</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=2 class=xl8516797>For the month of:</td>
                        <td colspan=5 class=xl9416797>{{ $from->format('F Y') }}</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7516797 style='border-left:none'>&nbsp;</td>
                        <td colspan=2 class=xl8516797>For the month of:</td>
                        <td colspan=5 class=xl9416797>{{ $from->format('F Y') }}</td>
                    </tr>
                    <tr height=19 style='mso-height-source:userset;height:14.25pt'>
                        <td colspan=3 rowspan=2 class=xl9216797 width=131 style='width:98pt'>Official hours for <br> arrival and departure </td>
                        <td colspan=2 class=xl9316797 width=96 style='width:72pt'>Regular Days</td>
                        <td colspan=2 class=xl8916797>08:00 to 16:00</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7616797 style='border-left:none'>&nbsp;</td>
                        <td colspan=3 rowspan=2 class=xl9216797 width=131 style='width:98pt'>Official hours for <br> arrival and departure </td>
                        <td colspan=2 class=xl9316797 width=96 style='width:72pt'>Regular Days</td>
                        <td colspan=2 class=xl8916797>08:00 to 16:00</td>
                    </tr>
                    <tr height=19 style='mso-height-source:userset;height:14.25pt'>
                        <td colspan=2 class=xl9316797 width=96 style='width:72pt'>Saturdays</td>
                        <td colspan=2 class=xl9016797>as required</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7616797 style='border-left:none'>&nbsp;</td>
                        <td colspan=2 class=xl9316797 width=96 style='width:72pt'>Saturdays</td>
                        <td colspan=2 class=xl9016797>as required</td>
                    </tr>
                    <tr style='line-height:3.0pt'>
                        <td colspan=7 class=xl8716797>&nbsp;</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7516797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl8716797>&nbsp;</td>
                    </tr>
                    <tr height=21 style='height:15.75pt'>
                        <td rowspan=2 class=xl9516797>Day</td>
                        <td colspan=2 class=xl9616797 style='border-left:none'>AM</td>
                        <td colspan=2 class=xl9616797 style='border-left:none'>PM</td>
                        <td colspan=2 class=xl9616797 style='border-left:none'>Undertime</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7716797 style='border-left:none'>&nbsp;</td>
                        <td rowspan=2 class=xl9516797>Day</td>
                        <td colspan=2 class=xl9616797 style='border-left:none'>AM</td>
                        <td colspan=2 class=xl9616797 style='border-left:none'>PM</td>
                        <td colspan=2 class=xl9616797 style='border-left:none'>Undertime</td>
                    </tr>
                    <tr height=21 style='height:15.75pt'>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Arr.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Dept.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Arr.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Dept.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Hr.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Min.</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7716797 style='border-left:none'>&nbsp;</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Arr.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Dept.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Arr.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Dept.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Hrs.</td>
                        <td class=xl9616797 style='border-top:none;border-left:none'>Mins.</td>
                    </tr>
                    @for ($day = 1; $day <= 31; $day++)

                        @php($date = $month->clone()->setDay($day))

                        @php(['in1' => $in1, 'in2' => $in2, 'out1' => $out1, 'out2' => $out2] = $timelog->logsForTheDay($employee, $date))

                        <tr height=21 style='height:15.75pt'>
                            @for($side = 1; $side <= 2; $side++)
                                <td class=xl9616797 style='border-top:none'>{{ $day }}</td>

                                {{-- IN1 --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>
                                    {{ $in1?->time->format('H:i') }}
                                </td>

                                {{-- OUT1 --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>
                                    {{ $out1?->time->format('H:i') }}
                                </td>

                                {{-- IN2 --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>
                                    {{ $in2?->time->format('H:i') }}
                                </td>

                                {{-- OUT2 --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>
                                    {{ $out2?->time->format('H:i') }}
                                </td>

                                {{-- UNDERTIME HOURS --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>

                                </td>

                                {{-- UNDERTIME MINUTES --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>

                                </td>

                                @if($side == 1)
                                    <td class=xl7316797></td>
                                    <td class=xl7416797 style='border-left:none'></td>
                                @endif
                            @endfor
                        </tr>
                    @endfor
                    <tr style='line-height:3.0pt'>
                        <td colspan=7>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td style='border-left:none'>&nbsp;</td>
                        <td colspan=7>&nbsp;</td>
                    </tr>
                    <tr height=20 style='height:15.0pt'>
                        <td colspan=2 class=xl8216797>TOTAL:</td>
                        <td colspan=5 class=xl8816797>&nbsp;</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7516797 style='border-left:none'>&nbsp;</td>
                        <td colspan=2 class=xl8216797>TOTAL:</td>
                        <td colspan=5 class=xl8816797>&nbsp;</td>
                    </tr>
                    <tr height=19 style='mso-height-source:userset;height:14.25pt'>
                        <td colspan=7 rowspan=3 class=xl7116797 width=323 style='width:242pt'>
                            I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                        </td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7816797 width=43 style='border-left:none;width:32pt'>&nbsp;</td>
                        <td colspan=7 rowspan=3 class=xl7116797 width=323 style='width:242pt'>
                            I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                        </td>
                    </tr>
                    <tr height=19 style='mso-height-source:userset;height:14.25pt'>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7816797 width=43 style='border-left:none;width:32pt'>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7816797 width=43 style='border-left:none;width:32pt'>&nbsp;</td>
                    </tr>
                    <tr height=20 style='height:15.0pt'>
                        <td colspan=7>&nbsp;</td>
                        <td class=xl8116797>&nbsp;</td>
                        <td class=xl7516797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7>&nbsp;</td>
                    </tr>
                    <tr height=20 style='height:15.0pt'>
                        <td colspan=7 class=xl8716797>&nbsp;</td>
                        <td class=xl8116797>&nbsp;</td>
                        <td class=xl7516797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl8716797>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=7 class=xl7016797>&nbsp;</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7416797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl7016797>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=7 class=xl7216797>Verified as to the prescribed office hours:</td>
                        <td class=xl7916797>&nbsp;</td>
                        <td class=xl8016797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl7216797>Verified as to the prescribed office hours:</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                    <tr height=20 style='height:15.0pt'>
                        <td colspan=7 class=xl8716797>&nbsp;</td>
                        <td class=xl8116797>&nbsp;</td>
                        <td class=xl7516797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl8716797>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=7 class=xl7016797>&nbsp;</td>
                        <td class=xl7316797>&nbsp;</td>
                        <td class=xl7416797 style='border-left:none'>&nbsp;</td>
                        <td colspan=7 class=xl7016797>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                    <tr height=19 style='height:14.25pt'>
                        <td colspan=8 class=xl7316797>&nbsp;</td>
                        <td colspan=8 class=xl7416797 style='border-left:none'>&nbsp;</td>
                    </tr>
                </table>
            @endforeach
        </div>
    </body>
</html>
>>>>>>> master
