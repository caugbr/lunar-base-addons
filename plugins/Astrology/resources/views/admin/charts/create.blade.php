@extends('admin.layout')
@section('header_title', 'Novo Mapa Astral')
@section('header_subtitle', 'Calcule e armazene as posições astronômicas de um nascimento')

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-admin.css') }}">
@endpush
@endonce

@section('content')
    @include('astrology::admin.charts._form', [
        'action' => route('admin.astrology.charts.store')
    ])
@endsection
