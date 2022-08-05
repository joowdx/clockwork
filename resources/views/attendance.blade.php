<x-slot name="title">
    Attendance
</x-slot>

<div class="py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden text-white bg-gray-900 shadow-xl sm:rounded-lg">

            <div class="flex px-5 pt-5 space-x-3" x-data="{ from: @entangle('from') }">
                <div class="w-1/5">
                    <x-jet-label for="search" class="text-white uppercase" value="{{ __('Search') }}" />
                    <x-jet-input id="search" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" type="text" name="search" :value="old('search')" required autofocus x-bind:placeholder="from" wire:model="search" />
                </div>
                <div class="w-1/5">
                    <x-jet-label for="from" class="text-white uppercase" value="{{ __('From') }}" />
                    <select id="from" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" wire:model="from">
                        <option>employees</option>
                        <option>offices</option>
                    </select>
                </div>
                <div class="w-1/5">
                    <x-jet-label for="office" class="text-white uppercase" value="{{ __('Office') }}" />
                    <select id="offices" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" wire:model="office" @disabled($from === 'offices')>
                        @foreach ($offices as $office)
                            <option> {{ $office }} </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-1/5">
                    <x-jet-label for="active" class="text-white uppercase" value="{{ __('Active') }}" />
                    <select id="active" class="inline-flex items-center w-full px-4 py-2 mt-1 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25" wire:model="active">
                        <option value="0">All</option>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="grid items-start grid-cols-12 p-5 md:space-x-3">
                @switch($from)
                    @case('offices')
                        <table class="col-span-12 table-auto md:col-span-4">
                            <thead>
                                <tr>
                                    <td class="w-1 pr-3">
                                        <x-jet-checkbox />
                                    </td>
                                    <td class="font-bold uppercase">
                                        Office
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($offices->filter() as $office)
                                    <tr>
                                        <td class="w-1 pr-3">
                                            <x-jet-checkbox />
                                        </td>
                                        <td>
                                            {{ $office }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @break
                    @case('employees')
                        <table class="col-span-12 table-auto md:col-span-4">
                            <thead>
                                <tr>
                                    <td class="w-1 pr-3">
                                        <x-jet-checkbox />
                                    </td>
                                    <td class="font-bold uppercase">
                                        Employees
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $employee)
                                    <tr>
                                        <td class="w-1 pr-3">
                                            <x-jet-checkbox />
                                        </td>
                                        <td>
                                            {{ $employee->name_format->fullStartLastInitialMiddle }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @break
                    @default
                @endswitch

                <table class="col-span-12 table-auto md:col-span-4">
                    <thead>
                        <tr>
                            <td class="w-1 pr-3">
                                <x-jet-checkbox />
                            </td>
                            <td class="font-bold uppercase">
                                Scanner
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($scanners as $scanner)
                            <tr>
                                <td class="w-1 pr-3">
                                    <x-jet-checkbox wire:model="selected.scanners.{{ $scanner->id }}" />
                                </td>
                                <td>
                                    {{ $scanner->name }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>



        </div>
    </div>
</div>
