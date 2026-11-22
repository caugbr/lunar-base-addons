@props([
    'type' => 'date',
    'name' => '',
    'id' => null,
    'value' => '',
    'label' => null,
    'modalTitle' => null,
    'placeholder' => null,
    'minYear' => null,
    'maxYear' => null,
    'breakpoint' => null,
])

@php
    $resolvedType = in_array($type, ['date', 'time', 'datetime-local'], true) ? $type : 'date';
    $resolvedBreakpoint = (int) ($breakpoint ?? config('pluginSettings.RollerPicker.defaultBreakpoint', 768));
    $resolvedMinYear = (int) ($minYear ?? config('pluginSettings.RollerPicker.defaultMinYear', 1930));
    $resolvedMaxYear = (int) ($maxYear ?? date('Y'));

    $defaultTitle = match ($resolvedType) {
        'time' => 'Selecionar hora',
        'datetime-local' => 'Selecionar data e hora',
        default => 'Selecionar data',
    };
    $resolvedTitle = $modalTitle ?? $label ?? $defaultTitle;

    $defaultPlaceholder = $placeholder ?? match ($resolvedType) {
        'time' => '00:00',
        'datetime-local' => 'DD/MM/AAAA --:--',
        default => 'DD/MM/AAAA',
    };

    $inputId = $id ?? ($name !== '' ? str_replace(['[', ']'], ['_', ''], $name) : 'roller_' . uniqid());
@endphp

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/roller-picker/css/roller-picker.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('plugins/roller-picker/js/roller-picker.js') }}"></script>
@endpush
@endonce

<div class="roller-universal-field"
     data-roller-picker
     data-type="{{ $resolvedType }}"
     data-breakpoint="{{ $resolvedBreakpoint }}"
     data-min-year="{{ $resolvedMinYear }}"
     data-max-year="{{ $resolvedMaxYear }}"
     data-title="{{ $resolvedTitle }}">
    @if($label)
        <label class="field-label" for="{{ $inputId }}">{{ $label }}</label>
    @endif

    <div class="input-wrapper">
        <input
            type="{{ $resolvedType }}"
            @if($name !== '') name="{{ $name }}" @endif
            id="{{ $inputId }}"
            value="{{ $value }}"
            placeholder="{{ $defaultPlaceholder }}"
            {{ $attributes->merge(['class' => 'roller-native-input']) }}
        >
        <button type="button" class="input-trigger" tabindex="-1" aria-hidden="true"></button>
    </div>
</div>
