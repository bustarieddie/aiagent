@extends('layouts.admin')
@section('title', 'Conversations')
@section('content')
<div class="min-h-screen flex" x-data="conversationsPage()" x-init="load()">

    {{-- Left: conversation list --}}
    <section class="w-96 shrink-0 border-r border-gray-100 bg-white flex flex-col">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Conversations</h2>
            <input x-model="q" @keyup.enter="load()" placeholder="Cari nama, nombor, mesej…" class="mt-2 w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5" />
        </div>
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
            <template x-for="c in rows" :key="c.phone">
                <div @click="select(c)" :class="selected?.phone === c.phone ? 'bg-emerald-50' : 'hover:bg-gray-50'" class="px-4 py-3 cursor-pointer">
                    <div class="flex justify-between items-baseline gap-2">
                        <div class="font-medium text-gray-900 text-sm truncate" x-text="c.name || c.phone"></div>
                        <div class="text-[10px] text-gray-400" x-text="formatTime(c.last_ts)"></div>
                    </div>
                    <div class="text-xs text-gray-500 font-mono" x-text="c.phone"></div>
                    <div class="text-xs text-gray-600 truncate mt-1" x-text="c.last_message"></div>
                </div>
            </template>
            <div x-show="!rows.length" class="p-8 text-center text-sm text-gray-400">Tiada conversation.</div>
        </div>
    </section>

    {{-- Right: thread --}}
    <section class="flex-1 min-w-0 flex flex-col bg-gray-50">
        <template x-if="selected">
            <div class="flex-1 flex flex-col">
                <div class="border-b border-gray-100 bg-white px-5 py-3">
                    <div class="font-semibold text-gray-900" x-text="selected.name || selected.phone"></div>
                    <div class="text-xs text-gray-500 font-mono" x-text="selected.phone"></div>
                </div>
                <div class="flex-1 overflow-y-auto p-5 space-y-2" x-ref="thread">
                    <template x-for="(m, i) in messages" :key="i">
                        <div :class="m.direction === 'in' ? 'justify-start' : 'justify-end'" class="flex">
                            <div :class="bubbleClass(m)" class="max-w-[75%] rounded-2xl px-3 py-2 text-sm whitespace-pre-wrap shadow-sm">
                                <template x-if="m.media_url">
                                    <img :src="mediaProxy(m.media_url)" class="max-w-full max-h-64 rounded-lg mb-1 object-contain bg-black/5" />
                                </template>
                                <div x-text="m.body"></div>
                                <div :class="m.direction === 'in' ? 'text-gray-400' : 'text-white/70'" class="text-[10px] mt-1">
                                    <span x-show="m.direction === 'out'" class="mr-1 uppercase tracking-wide" x-text="m.source === 'staff' ? 'staff' : 'bot'"></span>
                                    <span x-text="formatTime(m.timestamp)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <form @submit.prevent="send()" class="border-t border-gray-100 bg-white p-3">
                    <textarea x-model="draft" rows="2" placeholder="Reply sebagai staff… (Enter = hantar)" @keydown.enter.prevent="send()" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2"></textarea>
                    <button type="submit" class="mt-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-1.5">Hantar</button>
                </form>
            </div>
        </template>
        <template x-if="!selected">
            <div class="flex-1 flex items-center justify-center text-gray-400 text-sm">Pilih satu conversation.</div>
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
            if (m.direction === 'in') return 'bg-white text-gray-900 border border-gray-100 rounded-bl-sm';
            return m.source === 'staff' ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-emerald-600 text-white rounded-br-sm';
        },
        mediaProxy(botPath) {
            return botPath.replace(/^\/admin\/api\/media\//, '/admin/whatsapp-agent/api/media/');
        },
        formatTime(iso) {
            if (!iso) return '';
            try {
                const d = new Date(iso);
                return d.toLocaleTimeString('ms-MY', {hour: '2-digit', minute: '2-digit', hour12: false});
            } catch { return ''; }
        },
    };
}
</script>
@endpush
@endsection
