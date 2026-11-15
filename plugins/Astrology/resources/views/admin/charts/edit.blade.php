@extends('admin.layout')
@section('header_title', 'Editar Mapa Astral')
@section('header_subtitle', 'Atualize os dados e recalcule as efemérides: ' . $chart->name)

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-admin.css') }}">
@endpush
@endonce

@section('content')
    @include('astrology::admin.charts._form', [
        'action' => route('admin.astrology.charts.update', $chart->id),
        'chart'  => $chart
    ])
@endsection
