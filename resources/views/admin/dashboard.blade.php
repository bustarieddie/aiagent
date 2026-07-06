@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="p-6 space-y-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Dashboard</h2>
        <p class="text-sm text-gray-500">Snapshot harian Klinik Bustari WhatsApp AI Agent · {{ $today }}</p>
    </div>

    <section class="space-y-2">
        <h3 class="text-sm font-medium text-gray-700">Hari ini</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <x-stat-card label="Leads masuk hari ini" :value="$bot['today_new_leads'] ?? '—'" hint="pesakit baru hari ini" />
            <x-stat-card
                label="Total leads"
                :value="($bot['hot_leads'] ?? 0) + ($bot['warm_leads'] ?? 0) + ($bot['new_leads'] ?? 0) + ($bot['cold_leads'] ?? 0)"
                hint="semua tier digabung" />
            <x-stat-card label="Conversation terbuka" :value="$bot['open_conversations'] ?? '—'" tone="emerald" />
            <x-stat-card label="Booking dibuat hari ini" :value="$portal['bookingsToday'] ?? 0" />
        </div>
    </section>

    <section class="space-y-2">
        <h3 class="text-sm font-medium text-gray-700">Action queue</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('admin.dashboard') }}" class="block">
                <x-stat-card label="Draft menunggu approve" :value="$portal['draftPending']" />
            </a>
            <a href="{{ route('admin.conversations') }}?filter=takeover" class="block">
                <x-stat-card label="Human takeover aktif" :value="$portal['takeoverCount']" tone="rose" />
            </a>
            <x-stat-card label="AI dimatikan (manual mode)" :value="$portal['aiDisabledCount']" />
            <x-stat-card
                label="Hot leads"
                :value="$bot['hot_leads'] ?? 0"
                :hint="'Warm: ' . ($bot['warm_leads'] ?? 0) . ' · New: ' . ($bot['new_leads'] ?? 0)"
                tone="emerald" />
        </div>
    </section>

    <section class="space-y-2">
        <h3 class="text-sm font-medium text-gray-700">AI struggled (low-confidence)</h3>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            @if ($recentLowConfidence->isEmpty())
                <div class="text-center text-sm text-gray-400">Tiada event low-confidence baru-baru ini. 👍</div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($recentLowConfidence as $ev)
                        <li class="py-2 text-sm flex justify-between gap-3">
                            <span class="text-gray-700">{{ $ev->phone }} · {{ $ev->intent ?? '—' }}</span>
                            <span class="text-xs text-gray-500">{{ number_format($ev->confidence * 100, 1) }}% · {{ $ev->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</div>
@endsection
