@extends('app')

@section('head')

    @vite(['resources/js/inertia.js'])

@endsection

@section('body')

    @inertia

    @routes

@endsection
