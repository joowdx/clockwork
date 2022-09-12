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
            @foreach ($employees as $employee)
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

                        <tr height=21 style='height:15.75pt'>
                            @for($side = 1; $side <= 2; $side++)
                                <td class=xl9616797 style='border-top:none'>{{ $day }}</td>

                                {{-- IN1 --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>

                                </td>

                                {{-- OUT1 --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>

                                </td>

                                {{-- IN2 --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>

                                </td>

                                {{-- OUT2 --}}
                                <td class=xl9116797 style='border-top:none;border-left:none'>

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
