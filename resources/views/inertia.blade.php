@extends('app')

@push('head')

    <script src="{{ mix('js/inertia.js') }}" defer></script>

@endpush

@push('body')

    @inertia

    @routes

@endpush
