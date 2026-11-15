@props([
    'sign',
    'theme' => 'classic',
    'showName' => false,
    'class' => 'astrology-sign-img'
])

@php
    $data = \Plugins\Astrology\Services\ThemeManager::getSignData($sign, $theme);
@endphp

<span class="astrology-sign-wrapper" title="{{ $data['name'] }}">
    @if($data['iconUri'])
        <img src="{{ $data['iconUri'] }}" alt="{{ $data['name'] }}" class="{{ $class }}" />
    @else
        <span class="astrology-sign-unicode">{{ $data['unicode'] }}</span>
    @endif

    @if($showName)
        <span class="astrology-sign-label">{{ $data['name'] }}</span>
    @endif
</span>
