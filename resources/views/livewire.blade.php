@extends('app')

@push('head')

    <script src="{{ mix('js/livewire.js') }}" defer></script>

    @livewireStyles

@endpush

@push('body')

    {{ $slot }}

    @livewireScripts

@endpush
