@props(['label', 'value', 'hint' => null, 'tone' => 'gray'])

@php
    $toneClasses = [
        'gray' => 'text-gray-900',
        'emerald' => 'text-emerald-700',
        'rose' => 'text-rose-700',
        'amber' => 'text-amber-700',
    ];
    $valueClass = $toneClasses[$tone] ?? 'text-gray-900';
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
    <div class="text-[10px] uppercase tracking-wider text-gray-500">{{ $label }}</div>
    <div class="text-3xl font-semibold mt-1 {{ $valueClass }}">{{ $value }}</div>
    @if ($hint)
        <div class="text-[11px] text-gray-500 mt-0.5">{{ $hint }}</div>
    @endif
</div>
