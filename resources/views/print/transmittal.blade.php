@extends('print.layout')

@php($size ??= 'a4')

@php($preview ??= false)

@php($grouping ??= empty($groups) ? 'offices' : 'groups')

@php($grouper = fn ($employee) => $employee->$grouping->pluck($grouping === 'groups' ? 'name' : 'code')->toArray())

@php($pagination = match($size) { 'folio' => 40, 'legal' => 45, default => 30 } * 3)

@section('content')
    @foreach ($employees->loadMissing('office')->sortBy('full_name')->groupBy($grouper)->sortKeys() as $group => $employees)
        @continue($grouping === 'groups' && ! in_array($group, $groups ?? []))

        @php(
            $reciever = (str($grouping)->prepend('App\Models\\')->title()->singular()->toString())::query()
                ->where($grouping === 'offices' ? 'code' : 'name', $group)
                ->first()
        )

        @for ($copy = 0; (($copies ??= 1) < 0 ? 1 : $copies) > $copy; $copy++)
            @foreach ($employees->chunk($pagination) as $page => $employees)
                <div class="pagebreak" style="display:flex;align-items:center;justify-content:center;max-width:620pt;margin:auto;">
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
                        <col width=73 span=10>
                        <tbody>
                            @if (!$preview)
                                <tr></tr>
                                <tr>
                                    <td colspan="10" class="relative right">
                                        <span class="absolute" style="font-size:4.65pt;opacity:0.15;left:6pt;">ᜑᜊᜄᜆᜅ᜔ ᜇᜊᜏ᜔</span>

                                        @if (file_exists(storage_path('app/public/'.settings('seal'))))
                                            <img
                                                src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/'.settings('seal')))) }}"
                                                src="{{ url('storage/'.settings('seal')) }}"
                                                alt="davao-del-sur"
                                                class="absolute"
                                                style="width:48pt;top:15pt;left:0;"
                                            >
                                        @endif

                                        @if (($logo = auth()->user()?->employee?->currentDeployment?->office->logo) && file_exists(storage_path('app/public/'.$logo)))
                                            <img
                                                src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/'.$logo))) }}"
                                                alt="davao-del-sur"
                                                class="absolute"
                                                style="width:48pt;top:15pt;right:0;"
                                            >
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="10" class="uppercase center consolas">
                                        Republic of the Philippines
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="10" class="uppercase center courier bold font-xl" @style(['opacity:0.25' => empty(settings('name'))])>
                                        {{ settings('name') ?? 'Agency name not set' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="10" class="uppercase center bahnschrift-light font-sm" @style(['opacity:0.25' => empty(settings('address'))])>
                                        {{ settings('address') ?? 'Agency address not set' }}
                                    </td>
                                </tr>
                                @if ($size !== 'letter')
                                    <tr></tr>
                                @endif
                                <tr>
                                    <td colspan="10" class="underline uppercase courier center nowrap font-lg">
                                        {{ auth()->user()?->employee?->currentDeployment?->office->name }}
                                    </td>
                                </tr>
                                <tr></tr>
                                <tr>
                                    <td rowspan="3" colspan="10" class="arial" style="padding:0 1rem;vertical-align:top;">
                                        <div class="center font-lg bold" style="margin-bottom:3pt;">
                                            TRANSMITTAL
                                        </div>
                                        <div style="display:flex;gap:0.5rem;">
                                            <p style="margin:0;">
                                                Subject:
                                            </p>
                                            <p class="italic" style="text-decoration:underline;text-underline-offset:2pt;margin:0;">
                                                Daily time record printouts for
                                                <span class="bold" style="text-transform: capitalize">{{ $reciever->name }}</span>
                                                for the month of
                                                <span class="bold nowrap">{{ (isset($from, $to) ? $from . '-' . $to : '') . ' '. $month->format('F Y') }}</span>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                <tr></tr>
                                <tr></tr>
                                <tr></tr>
                                @if (in_array($size, ['a4']))
                                    <tr></tr>
                                @endif
                                <tr>
                                    <td rowspan="2" colspan="10" style="padding:0 1rem;text-indent:2rem;" class="arial">
                                        Attached herewith, are the daily time record printouts of the committed employees of this office, to wit:
                                    </td>
                                </tr>
                                <tr></tr>
                                <tr></tr>
                                @for ($i = 0; $i < $pagination / 3; $i++)
                                    @php($index = $page * $pagination + $i)
                                    <tr class="courier">
                                        <td colspan="10">
                                            <div class="font-sm" style="display:flex;padding:0 1.5rem;">
                                                <div class="nowrap" style="padding-right:0.5em;width:33.3333%;text-overflow:ellipsis;overflow:hidden;">
                                                    @php($employee = @($employees[$index])?->name)
                                                    {{ $employee ? str($index + 1)->padLeft(2, 0)->append(". $employee") : '' }}
                                                </div>
                                                <div class="nowrap" style="padding-right:0.5em;width:33.3333%;text-overflow:ellipsis;overflow:hidden;">
                                                    @php($employee = @($employees[$index + ($pagination / 3)])?->name)
                                                    {{ $employee ? str($index + ($pagination / 3) + 1)->padLeft(2, 0)->append(". $employee") : '' }}
                                                </div>
                                                <div class="nowrap" style="padding-right:0.5em;width:33.3333%;text-overflow:ellipsis;overflow:hidden;">
                                                    @php($employee = @($employees[$index + ($pagination / 3 * 2)])?->name)
                                                    {{ $employee ? str($index + ($pagination / 3 * 2) + 1)->padLeft(2, 0)->append(". $employee") : '' }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endfor
                                <tr></tr>
                                <tr>
                                    <td colspan="10" style="padding:0 1rem;text-indent:2rem" class="arial">
                                        By signing below, I hereby acknowledge that our office has received and reviewed the daily time record printouts of all the employees listed above.
                                    </td>
                                </tr>
                                <tr></tr>
                                @if (in_array($size, ['a4', 'letter']))
                                    <tr></tr>
                                @endif
                                <tr>
                                    <td colspan="4" class="center" style="padding-left:1rem;">
                                    </td>
                                    <td colspan="2">

                                    </td>
                                    <td colspan="4" class="uppercase center consolas font-lg" style="padding-right:1rem;">
                                        @includeWhen($signature ??= null, 'print.signature', ['signature' => $signature, 'signed' => $signed ?? false])
                                        {{ auth()->user()?->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="uppercase cascadia center font-sm" style="padding-left:1rem;">
                                        <div class="overline">
                                            Personnel-in-charge
                                        </div>
                                    </td>
                                    <td colspan="2">

                                    </td>
                                    <td colspan="4" class="uppercase cascadia center font-sm" style="padding-right:1rem;">
                                        <div class="overline">
                                            Officer-in-charge
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endfor
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
