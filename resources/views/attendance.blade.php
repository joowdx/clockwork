<x-slot name="title">
    Attendance
</x-slot>

<div class="py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8" x-data="{
        url: @entangle('url'),
        generated: false,
    }">
        <div class="overflow-hidden text-white bg-gray-900 shadow-xl sm:rounded-lg">

            <div class="grid grid-cols-12 gap-3 p-5" x-data="{ from: @entangle('from') }">
                <div class="col-span-6 md:col-span-3">
                    <x-jet-label for="search" class="text-white uppercase" value="{{ __('Search') }}" />
                    <x-jet-input id="search" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" type="text" name="search" :value="old('search')" required autofocus x-bind:placeholder="'Scanners / ' + from" wire:model.debounce.250ms="search" disabled="{{ (bool) $url }}" />
                </div>
                <div class="col-span-6 md:col-span-3">
                    <x-jet-label for="from" class="text-white uppercase" value="{{ __('From') }}" />
                    <select id="from" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" wire:model="from" @disabled($url) >
                        <option>employees</option>
                        <option>offices</option>
                    </select>
                </div>
                <div class="col-span-6 md:col-span-3">
                    <x-jet-label for="start" class="text-white uppercase" value="{{ $from == 'offices' ? __('Date') : __('Start') }}" />
                    <x-jet-input id="start" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" type="date" name="start" :value="old('start')" required autofocus x-bind:placeholder="from" wire:model="start" disabled="{{ (bool) $url }}" />
                </div>
                @if ($from !== 'offices')
                    <div class="col-span-6 md:col-span-3">
                        <x-jet-label for="end" class="text-white uppercase" value="{{ __('End') }}" />
                        <x-jet-input id="end" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" type="date" name="end" :value="old('end')" required autofocus x-bind:placeholder="from" wire:model="end" disabled="{{ (bool) $url }}" />
                    </div>
                    <div class="col-span-6 md:col-span-3">
                        <x-jet-label for="office" class="text-white uppercase" value="{{ __('Office') }}" />
                        <select id="offices" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" wire:model="office" @disabled($url) >
                            @foreach ($offices as $office)
                                <option> {{ $office }} </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-span-6 md:col-span-3">
                    <x-jet-label for="active" class="text-white uppercase" value="{{ __('Active') }}" />
                    <select id="active" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" wire:model="active" @disabled($url) >
                        <option value=""></option>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-5">
                <x-jet-button @click="generated = !url" wire:click="generate" x-html="url ? 'Close' : 'Generate'" wire:target="generate" wire:loading.attr="disabled"> Generate </x-jet-button>
                <x-jet-button @click="document.getElementById('print').contentWindow.print()" x-bind:disabled="! url && ! generated" disabled wire:target="generate" wire:loading.attr="disabled"> Print </x-jet-button>
            </div>

            @if (! empty($errors->all()))
                <div class="px-5 text-red-500">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if ($url)
                <div class="flex justify-center p-5 ">
                    <iframe id="print" class="w-full h-screen rounded lg:w-3/4" x-bind:src="url" x-bind:title="@entangle('title')" x-on:load="generated = true"></iframe>
                </div>
            @else
                <div class="grid items-start grid-cols-12 gap-3 p-5">
                    <div class="col-span-12 md:col-span-4">
                        <table class="table-auto">
                            <thead>
                                <tr>
                                    <td colspan="2" class="font-bold uppercase">
                                        Scanner ({{ collect($selected['scanners'])->filter()->count() }})
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($scanners as $scanner)
                                    <tr>
                                        <td class="w-1 pr-3">
                                            <x-jet-checkbox wire:model="selected.scanners.{{ $scanner->id }}" />
                                        </td>
                                        <td>
                                            {{ $scanner->name }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td cols-pan="2" class="text-gray-500">
                                            We've come up empty.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @switch($from)
                        @case('offices')
                            <div class="col-span-12 md:col-span-4">
                                <table class="table-auto">
                                    <thead>
                                        <tr>
                                            <td colspan="2" class="font-bold uppercase">
                                                Offices ({{ collect(@$selected['offices'])->filter()->count() }})
                                            </td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($offices->filter() as $office)
                                            <tr>
                                                <td class="w-1 pr-3">
                                                    <x-jet-checkbox wire:model="selected.offices.{{ $office }}"/>
                                                </td>
                                                <td>
                                                    {{ $office }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td cols-pan="2" class="text-gray-500">
                                                    We've come up empty.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @break
                        @case('employees')
                            <div class="col-span-12 md:col-span-4">
                                <table class="table-auto">
                                    <thead>
                                        <tr>
                                            <td colspan="2" class="font-bold uppercase">
                                                Employees ({{ collect(@$selected['employees'])->filter()->count() }})
                                            </td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($employees as $employee)
                                            <tr>
                                                <td class="w-1 pr-3">
                                                    <x-jet-checkbox wire:model="selected.employees.{{ $employee->id }}"/>
                                                </td>
                                                <td>
                                                    {{ $employee->name_format->fullStartLastInitialMiddle }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td cols-pan="2" class="text-gray-500">
                                                    We've come up empty.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                {{ $employees->links() }}
                            </div>
                            @break
                        @default
                    @endswitch
                </div>
            @endif


        </div>
    </div>
</div>

@push('head')
    <style>
        :root {
            color-scheme: dark;
        }

        [x-cloak] { display: none !important; }
    </style>
@endpush
