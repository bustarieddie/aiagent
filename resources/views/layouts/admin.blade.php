<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WhatsApp AI Agent') · Klinik Bustari</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="min-h-screen flex">

        {{-- Sidebar --}}
        <aside class="w-60 shrink-0 bg-white border-r border-gray-100 flex flex-col">
            <div class="px-5 py-5 border-b border-gray-100">
                <h1 class="font-semibold text-gray-900 leading-tight">WhatsApp AI Agent</h1>
                <p class="text-xs text-gray-500 mt-0.5">Klinik Bustari</p>
            </div>
            <nav class="flex-1 px-2 py-3 space-y-1 overflow-y-auto">
                @php
                    $nav = [
                        ['admin.dashboard',      'Dashboard',      '📊'],
                        ['admin.conversations',  'Conversations',  '💬'],
                        ['admin.leads',          'Leads',          '👥'],
                        ['admin.patients',       'Patients',       '🧑‍⚕️'],
                        ['admin.panels',         'Panels',         '🏥'],
                        ['admin.broadcast',      'Broadcast',      '📣'],
                        ['admin.automation',     'Automation',     '🤖'],
                        ['admin.staff',          'Staff',          '🧑‍💼'],
                    ];
                @endphp
                @foreach ($nav as [$route, $label, $icon])
                    @php $active = request()->routeIs($route); @endphp
                    <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors {{ $active ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <span class="text-base">{{ $icon }}</span>
                        <span class="flex-1">{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="px-2 pb-4">
                @csrf
                <button type="submit" class="w-full text-left text-sm text-gray-500 hover:text-gray-900 px-3 py-2 rounded-lg hover:bg-gray-50">
                    ← Log keluar
                </button>
            </form>
            <div class="px-5 pb-4 text-[10px] text-gray-400 leading-snug">
                Laravel · WaSenderAPI · Claude Haiku
            </div>
        </aside>

        {{-- Main --}}
        <main class="flex-1 min-w-0">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
