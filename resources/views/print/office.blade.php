<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
        @vite(['resources/css/print.css'])
        <title>ATTENDANCE</title>
    </head>
    <body>
        @foreach ($offices as $office => $employees)
            @php
                $pages = ceil(max(count(@$employees['nonregular'] ?? []), count(@$employees['regular'] ?? [])) / 30)
            @endphp
            @for ($i = 0; $i < ($pages ?: 1); $i++)
                <div class="pagebreak" align="center" style="padding:25pt;">
                    <table border="0" cellpadding="0" cellspacing="0" width="640">
                        <tbody>
                            <tr height="20">
                                <td colspan="10" rowspan="2" height="40" class="bold center bahnschrift font-xl" width="640" style="height:30.0pt;width:480pt">ATTENDANCE</td>
                            </tr>
                            <tr height="20"></tr>
                            <tr height="20"></tr>
                            <tr height="20">
                                <td height="20" class="font-md bold bottom bahnschrift">OFFICE</td>
                                <td colspan="9" class="uppercase font-md bottom consolas">
                                    {{ $office }}
                                </td>
                            </tr>
                            <tr height="20">
                                <td height="20" class="font-md bold bottom bahnschrift">DATE</td>
                                <td colspan="9" class="uppercase font-md bottom consolas">
                                    {{ $date->format('D d-M-Y') }}
                                </td>
                            </tr>
                            <tr height="22" style="height:16.5pt">
                                <td colspan="4" height="22" class="underline bold bottom nowrap cascadia font-md" style="height:16.5pt">REGULAR</td>
                                <td class="underline bold bottom nowrap cascadia font-md" style="border-right:solid;border-right-width:1pt;border-right-color:white;">TIME</td>
                                <td colspan="4" height="22" class="underline bold bottom nowrap cascadia font-md" style="height:16.5pt;">JO, COS, ETC.</td>
                                <td class="underline bold bottom nowrap cascadia font-md">TIME</td>
                            </tr>
                            @for ($j = 0; $j < 31; $j++)
                                <tr height="20">
                                    @if ($pages == 0 && $j == 0)
                                        <td colspan="5" height="20" class="font-sm nowrap consolas">
                                            ⏴⏴⏴⏴⏴⏴⏴⏴<b>BLANK ({{ $i + 1 . '/' . 1 }})</b>⏵⏵⏵⏵⏵⏵⏵⏵
                                        </td>
                                        <td colspan="5" height="20" class="font-sm nowrap consolas">
                                            ⏴⏴⏴⏴⏴⏴⏴⏴<b>BLANK ({{ $i + 1 . '/' . 1 }})</b>⏵⏵⏵⏵⏵⏵⏵⏵
                                        </td>
                                    @elseif ($pages == 0 && $j > 0)
                                        <td colspan="5" height="20">
                                            @for ($l = 0; $l <= 12; $l++)
                                                ∙ &nbsp; &nbsp;
                                            @endfor
                                        </td>
                                        <td colspan="5" height="20">
                                            @for ($l = 0; $l <= 12; $l++)
                                                ∙ &nbsp; &nbsp;
                                            @endfor
                                        </td>
                                    @else
                                        @for ($k = 0; $k < 2; $k++)
                                            @php
                                                $count = count($k == 0 ? @$employees['regular'] ?? []: @$employees['nonregular'] ?? []);
                                            @endphp
                                            @if ($j == 30 && $j + $i * 30 < $count)
                                                <td colspan="5" height="20" class="font-sm nowrap consolas" style="height:15.0pt;">
                                                    ━ ━ ━<b> CONTINUED ON NEXT PAGE ({{ $i + 1 . '/' . $pages }}) </b>━ ━ ━
                                                </td>
                                            @elseif ($j + $i * 30 < $count)
                                                @php
                                                    $employee = ($k == 0 ? $employees['regular'] : $employees['nonregular'])[$j + $i * 30];
                                                @endphp
                                                <td colspan="4" height="20" class="font-sm nowrap consolas">
                                                    {{ str_pad($j + $i * 30 + 1, 2, '0', STR_PAD_LEFT) . '. '. $employee->ellipsize() }}
                                                </td>
                                                <td height="20">
                                                    <div class="font-sm nowrap consolas bold {{ $employee->timelogs->sortBy('time')->first()?->scanner->name }}">
                                                        {{ $employee->timelogs->sortBy('time')->pluck('time')->first()?->format('H:i') }}
                                                    </div>
                                                </td>
                                            @elseif ($j == 0 && $j + $i * 30 >= $count)
                                                <td colspan="5" height="20" class="font-sm nowrap consolas">
                                                    ⏴⏴⏴⏴⏴⏴⏴⏴<b>BLANK ({{ $i + 1 . '/' . $pages }})</b>⏵⏵⏵⏵⏵⏵⏵⏵
                                                </td>
                                            @elseif ($j + $i * 30 == $count)
                                                <td colspan="5" height="20" class="font-sm nowrap consolas">
                                                    🗙🗙🗙🗙🗙 <b>NOTHING FOLLOWS ({{ $i + 1 . '/' . $pages }})</b>🗙🗙🗙🗙🗙
                                                </td>
                                            @else
                                                <td colspan="5" height="20">
                                                    @for ($l = 0; $l <= 12; $l++)
                                                        ∙ &nbsp; &nbsp;
                                                    @endfor
                                                </td>
                                            @endif
                                        @endfor
                                    @endif
                                </tr>
                            @endfor
                            <tr height="20"></tr>
                            <tr height="20"></tr>
                            <tr height="20">
                                <td class="bold bottom nowrap cascadia font-md">
                                    @if (@$employees['scanners']?->isNotEmpty())
                                        SCANNERS
                                    @endif
                                </td>
                            </tr>
                            @foreach (@$employees['scanners']?->chunk(5) ?? [] as $chunk)
                                <tr height="20">
                                    @foreach ($chunk as $scanner)
                                        <td colspan="2" class="uppercase font-xs nowrap consolas scanner bold {{ $scanner->name }}">
                                            {{ $scanner->name }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            @for ($j = 0; $j < 5; $j++)
                                <tr height="20"></tr>
                            @endfor
                            <tr height="20">
                                <td colspan="6"></td>
                                <td colspan="4" class="underline uppercase bold center bottom nowrap bahnschrift">
                                    {{ auth()->user()?->name }}
                                </td>
                            </tr>
                            <tr height="20">
                                <td colspan="3" height="20" class="font-xs nowrap consolas bold">
                                    DATE: <span class="uppercase">{{ now()->format('d M Y H:i') }}</span>
                                </td>
                                <td colspan="3"></td>
                                <td colspan="4" class="center nowrap bahnschrift-light">
                                    {{ auth()->user()?->title }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endfor
        @endforeach
    </body>
    <style>
        @foreach ($offices as $office => $employees)
            @foreach (@$employees['scanners'] ?? [] as $scanner)
                .{{$scanner->name}} {
                    background-color: {{$scanner->print_background_colour}};
                    color: {{$scanner->print_text_colour}};
                    width: fit-content;
                }
            @endforeach
        @endforeach
    </style>
</html>
