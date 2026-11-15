@php
    $themeSlug = $theme ?? 'classic';
    $containerId = 'chart-embed-' . $chart->slug . '-' . uniqid();
@endphp

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-public.css') }}">
@endpush
@endonce

<div class="natal-chart-embed-wrapper">
    @if($chart->name)
        <h3 class="natal-chart-embed-title">{{ $chart->name }}</h3>
        <p class="natal-chart-embed-date">{{ $chart->formatted_date_time }}</p>
    @endif

    <div id="{{ $containerId }}" class="natal-chart-viewport">
        {!! $chart->getRenderedSvg($themeSlug) !!}
    </div>
</div>
