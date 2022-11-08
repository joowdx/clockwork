@extends('app')

@section('head')

    @vite(['resources/css/app.css', 'resources/js/inertia.js'])

@endsection

@section('body')

    @inertia

    @routes

@endsection
