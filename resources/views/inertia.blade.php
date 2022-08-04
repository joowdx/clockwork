@extends('app')

@section('head')

    <script src="{{ mix('js/inertia.js') }}" defer></script>

@endsection

@section('body')

    @inertia

    @routes

@endsection
