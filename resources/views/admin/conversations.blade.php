@extends('layouts.admin')
@section('title', 'Conversations')
@section('content')
<div class="h-[calc(100vh-56px)] flex" x-data="conversationsPage()" x-init="load()">

    {{-- Left: conversation list --}}
    <section class="w-80 shrink-0 border-r border-gray-200 bg-white flex flex-col">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900 text-sm">Conversations</h2>
            <input x-model="q" @keyup.enter="load()" placeholder="Cari nama, nombor, mesej…" class="mt-2 w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500" />
        </div>
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
            <template x-for="c in rows" :key="c.phone">
                <div @click="select(c)" :class="selected?.phone === c.phone ? 'bg-emerald-50' : 'hover:bg-gray-50'" class="px-4 py-3 cursor-pointer">
                    <div class="flex justify-between items-baseline gap-2">
                        <div class="font-medium text-gray-900 text-sm truncate" x-text="c.name || c.phone"></div>
                        <div class="text-[10px] text-gray-400 shrink-0" x-text="formatTime(c.last_ts)"></div>
                    </div>
                    <div class="text-[11px] text-gray-500 font-mono" x-text="c.phone"></div>
                    <div class="text-xs text-gray-600 truncate mt-0.5" x-text="c.last_message"></div>
                </div>
            </template>
            <div x-show="!rows.length" class="p-8 text-center text-sm text-gray-400">Tiada conversation.</div>
        </div>
    </section>

    {{-- Right: thread --}}
    <section class="flex-1 min-w-0 flex flex-col" style="background-color: #efeae2;">
        <template x-if="selected">
            <div class="flex-1 flex flex-col min-h-0">
                {{-- Header --}}
                <div class="shrink-0 border-b border-gray-200 bg-white px-5 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-semibold text-sm shrink-0" x-text="initial(selected.name || selected.phone)"></div>
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900 text-sm truncate" x-text="selected.name || selected.phone"></div>
                        <div class="text-xs text-gray-500 font-mono" x-text="selected.phone"></div>
                    </div>
                </div>

                {{-- Thread --}}
                <div class="flex-1 overflow-y-auto px-4 py-4 space-y-1" x-ref="thread">
                    <template x-for="(m, i) in messages" :key="i">
                        <div>
                            {{-- Date separator --}}
                            <template x-if="showDateSeparator(i)">
                                <div class="flex justify-center my-3">
                                    <span class="bg-white/80 text-gray-600 text-[11px] px-3 py-1 rounded-md shadow-sm" x-text="formatDate(m.timestamp)"></span>
                                </div>
                            </template>
                            <div :class="m.direction === 'in' ? 'justify-start' : 'justify-end'" class="flex">
                                <div :class="bubbleClass(m)" :style="bubbleStyle(m)" class="max-w-[65%] rounded-lg px-2.5 py-1.5 text-sm whitespace-pre-wrap shadow-sm">
                                    <template x-if="m.media_url">
                                        <img :src="mediaProxy(m.media_url)" class="max-w-full max-h-64 rounded-md mb-1 object-contain bg-black/5" />
                                    </template>
                                    <div x-text="m.body" class="leading-snug"></div>
                                    <div :class="m.direction === 'in' ? 'text-gray-400' : 'text-white/80'" class="text-[10px] mt-0.5 text-right">
                                        <span x-show="m.direction === 'out'" class="mr-1 uppercase tracking-wide" x-text="m.source === 'staff' ? 'staff' : 'bot'"></span>
                                        <span x-text="formatTime(m.timestamp)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Composer --}}
                <form @submit.prevent="send()" class="shrink-0 border-t border-gray-200 bg-white p-3 flex gap-2 items-end">
                    <textarea x-model="draft" rows="1" placeholder="Reply sebagai staff… (Enter = hantar, Shift+Enter = newline)" @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }" class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-1 focus:ring-emerald-500" style="max-height: 120px;"></textarea>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-2 shrink-0">Hantar</button>
                </form>
            </div>
        </template>
        <template x-if="!selected">
            <div class="flex-1 flex items-center justify-center text-gray-500 text-sm">Pilih satu conversation.</div>
        </template>
    </section>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function conversationsPage() {
    return {
        q: '', rows: [], selected: null, messages: [], draft: '',
        async load() {
            const url = new URL('/admin/whatsapp-agent/api/conversations', window.location.origin);
            if (this.q.trim()) url.searchParams.set('q', this.q.trim());
            url.searchParams.set('limit', '100');
            const r = await fetch(url, {credentials: 'same-origin'});
            const data = await r.json();
            this.rows = Array.isArray(data) ? data : (data.conversations || []);
        },
        async select(c) {
            this.selected = c;
            const r = await fetch(`/admin/whatsapp-agent/api/conversations?phone=${encodeURIComponent(c.phone)}&limit=300`, {credentials: 'same-origin'});
            const data = await r.json();
            this.messages = data.messages || [];
            this.$nextTick(() => { this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight; });
        },
        async send() {
            if (!this.draft.trim() || !this.selected) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch('/admin/whatsapp-agent/api/send', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({phone: this.selected.phone, message: this.draft}),
            });
            if (r.ok) { this.draft = ''; await this.select(this.selected); }
        },
        bubbleClass(m) {
            if (m.direction === 'in') return 'bg-white text-gray-900 rounded-tl-none';
            return m.source === 'staff' ? 'bg-blue-500 text-white rounded-tr-none' : 'text-white rounded-tr-none';
        },
        bubbleStyle(m) {
            if (m.direction === 'out' && m.source !== 'staff') return 'background-color: #005c4b;';
            return '';
        },
        mediaProxy(botPath) {
            return botPath.replace(/^\/admin\/api\/media\//, '/admin/whatsapp-agent/api/media/');
        },
        parseTs(v) {
            if (!v) return null;
            // SQLite/MySQL "YYYY-MM-DD HH:MM:SS" isn't valid ISO — treat as UTC.
            if (typeof v === 'string' && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/.test(v)) {
                v = v.replace(' ', 'T') + 'Z';
            }
            // Unix seconds
            if (typeof v === 'number' && v < 1e12) v = v * 1000;
            const d = new Date(v);
            return isNaN(d.getTime()) ? null : d;
        },
        formatTime(v) {
            const d = this.parseTs(v);
            if (!d) return '';
            return d.toLocaleTimeString('ms-MY', {hour: '2-digit', minute: '2-digit', hour12: false});
        },
        formatDate(v) {
            const d = this.parseTs(v);
            if (!d) return '';
            const today = new Date();
            const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1);
            const isSameDay = (a, b) => a.toDateString() === b.toDateString();
            if (isSameDay(d, today)) return 'Hari ini';
            if (isSameDay(d, yesterday)) return 'Semalam';
            return d.toLocaleDateString('ms-MY', {day: '2-digit', month: 'short', year: 'numeric'});
        },
        showDateSeparator(i) {
            if (i === 0) return true;
            const prev = this.parseTs(this.messages[i - 1]?.timestamp);
            const curr = this.parseTs(this.messages[i]?.timestamp);
            if (!prev || !curr) return false;
            return prev.toDateString() !== curr.toDateString();
        },
        initial(s) {
            if (!s) return '?';
            const t = s.trim().replace(/^\+?60/, '');
            return t.charAt(0).toUpperCase();
        },
    };
}
</script>
@endpush
@endsection
