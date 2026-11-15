@props([
    'value' => '',
    'theme' => 'classic'
])

@php
    $signsRegex = '/\b(Aries|Taurus|Gemini|Cancer|Leo|Virgo|Libra|Scorpio|Sagittarius|Capricorn|Aquarius|Pisces|Áries|Touro|Gêmeos|Câncer|Leão|Virgem|Libra|Escorpião|Sagitário|Capricórnio|Aquário|Peixes)\b/i';

    $matched = preg_match($signsRegex, $value, $matches, PREG_OFFSET_CAPTURE);
@endphp

@if($matched)
    @php
        $signName = $matches[0][0];
        $offset = $matches[0][1];
        $length = strlen($signName);

        $before = trim(substr($value, 0, $offset));
        $after = trim(substr($value, $offset + $length));
        $signData = \Plugins\Astrology\Services\ThemeManager::getSignData($signName, $theme);
    @endphp
    <span class="astrology-position-inline" title="{{ astro_pos_pt($value) }}">
        @if($before)<span class="astrology-pos-deg">{{ $before }}</span>@endif
        <span class="astrology-pos-symbol">
            @if($signData['iconUri'])
                <img src="{{ $signData['iconUri'] }}" alt="{{ $signData['name'] }}" class="astrology-pos-icon" />
            @else
                <span class="astrology-pos-unicode">{{ $signData['unicode'] }}</span>
            @endif
        </span>
        @if($after)<span class="astrology-pos-min">{{ $after }}</span>@endif
    </span>
@else
    <code>{{ $value }}</code>
@endif
