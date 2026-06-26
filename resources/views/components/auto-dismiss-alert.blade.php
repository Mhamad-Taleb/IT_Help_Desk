@props([
    'type' => 'success',
    'message' => null,
    'timeout' => 3000,
])

@php
    $styles = match ($type) {
        'error' => 'border-rose-200 bg-rose-50 text-rose-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-800',
    };
@endphp

@if (filled($message))
    <div
        x-data="{ visible: true }"
        x-init="setTimeout(() => visible = false, {{ (int) $timeout }})"
        x-show="visible"
        x-transition.opacity.duration.500ms
        class="rounded-2xl border px-5 py-4 text-sm font-medium shadow-sm {{ $styles }}"
    >
        {{ $message }}
    </div>
@endif
