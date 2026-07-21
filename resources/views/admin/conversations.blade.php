@extends('layouts.admin')
@section('title', 'Conversations')
@section('content')
<div class="h-[calc(100vh-56px)] flex" x-data="conversationsPage()" x-init="init()">

    {{-- Left: conversation list --}}
    <section class="w-96 shrink-0 border-r border-gray-200 bg-white flex flex-col">
        <div class="px-4 py-3 border-b border-gray-200 space-y-2">
            <h2 class="font-semibold text-gray-900 text-sm">Conversations</h2>
            <input x-model="q" @keyup.enter="load()" placeholder="Cari nama, nombor, mesej…" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500" />
            {{-- Filter chips --}}
            <div class="flex flex-wrap gap-1">
                <template x-for="f in filters" :key="f.key">
                    <button @click="filter = f.key"
                        :class="filter === f.key ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="text-xs px-2.5 py-1 rounded-full font-medium flex items-center gap-1">
                        <span x-text="f.label"></span>
                        <span :class="filter === f.key ? 'bg-white/25' : 'bg-white'" class="text-[10px] px-1.5 py-0.5 rounded-full" x-text="countFor(f.key)"></span>
                    </button>
                </template>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
            <template x-for="(c, i) in filteredRows()" :key="c.phone || ('__idx_' + i)">
                <div @click="select(c)" :class="selected?.phone === c.phone ? 'bg-emerald-50' : 'hover:bg-gray-50'" class="px-4 py-3 cursor-pointer">
                    <div class="flex justify-between items-baseline gap-2">
                        <div class="font-medium text-gray-900 text-sm truncate" x-text="c.name || c.phone"></div>
                        <div class="text-[10px] text-gray-400 shrink-0 whitespace-nowrap" x-text="formatStamp(c.last_ts)"></div>
                    </div>
                    <div class="text-[11px] text-gray-500 font-mono" x-text="c.phone"></div>
                    <div class="text-xs text-gray-600 truncate mt-0.5" x-text="c.last_message"></div>
                    <div class="flex flex-wrap gap-1 mt-1.5">
                        <span x-show="c.crm_stage" :class="stageColor(c.crm_stage)" class="text-[10px] px-1.5 py-0.5 rounded" x-text="c.crm_stage"></span>
                        <span x-show="c.lead_tier" :class="tierColor(c.lead_tier)" class="text-[10px] px-1.5 py-0.5 rounded" x-text="c.lead_tier"></span>
                        <span x-show="c.flag?.humanTakeover" class="text-[10px] px-1.5 py-0.5 rounded bg-orange-100 text-orange-700">takeover</span>
                        <span x-show="c.flag?.status === 'closed'" class="text-[10px] px-1.5 py-0.5 rounded bg-gray-200 text-gray-600">closed</span>
                    </div>
                </div>
            </template>
            <div x-show="!filteredRows().length" class="p-8 text-center text-sm text-gray-400">Tiada conversation.</div>
        </div>
    </section>

    {{-- Right: thread --}}
    <section class="flex-1 min-w-0 flex flex-col" style="background-color: #efeae2;">
        <template x-if="selected">
            <div class="flex-1 flex flex-col min-h-0">
                {{-- Header --}}
                <div class="shrink-0 border-b border-gray-200 bg-white px-5 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-semibold text-sm shrink-0" x-text="initial(selected.name || selected.phone)"></div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-gray-900 text-sm truncate" x-text="selected.name || selected.phone"></div>
                            <div class="text-xs text-gray-500 flex items-center gap-1.5 flex-wrap">
                                <span class="font-mono" x-text="selected.phone"></span>
                                <template x-if="selected.crm_stage"><span>· <span x-text="selected.crm_stage"></span></span></template>
                                <template x-if="selected.lead_tier"><span>· <span x-text="selected.lead_tier"></span></span></template>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 shrink-0" x-text="messages.length + ' mesej'"></div>
                    </div>
                    {{-- Action buttons --}}
                    <div class="flex flex-wrap gap-1.5 mt-2.5">
                        <button @click="toggleFlag('aiEnabled', !flag.aiEnabled)"
                            :class="flag.aiEnabled ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-gray-100 text-gray-500 border-gray-300'"
                            class="text-xs border rounded-full px-3 py-1 font-medium">
                            AI: <span x-text="flag.aiEnabled ? 'ON' : 'OFF'"></span>
                        </button>
                        <button @click="toggleFlag('humanTakeover', !flag.humanTakeover)"
                            :class="flag.humanTakeover ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                            class="text-xs border rounded-full px-3 py-1 font-medium">
                            Human Takeover
                        </button>
                        <button @click="toggleFlag('status', flag.status === 'closed' ? 'open' : 'closed')"
                            :class="flag.status === 'closed' ? 'bg-gray-600 text-white border-gray-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                            class="text-xs border rounded-full px-3 py-1 font-medium">
                            <span x-text="flag.status === 'closed' ? 'Buka Semula' : 'Tandakan Selesai'"></span>
                        </button>
                        <button @click="deleteConversation()" class="text-xs border border-rose-300 text-rose-600 hover:bg-rose-50 rounded-full px-3 py-1 font-medium">
                            🗑 Padam
                        </button>
                    </div>
                </div>

                {{-- Thread --}}
                <div class="flex-1 overflow-y-auto px-4 py-4 space-y-1" x-ref="thread">
                    <template x-for="(m, i) in messages" :key="i">
                        <div>
                            <template x-if="showDateSeparator(i)">
                                <div class="flex justify-center my-3">
                                    <span class="bg-white/80 text-gray-600 text-[11px] px-3 py-1 rounded-md shadow-sm" x-text="formatDate(msgTs(m))"></span>
                                </div>
                            </template>
                            <div :class="m.direction === 'in' ? 'justify-start' : 'justify-end'" class="flex items-start">
                                <div :class="bubbleClass(m)" :style="bubbleStyle(m)" class="rounded-lg px-2 py-1 shadow-sm inline-block">
                                    <template x-if="m.media_url">
                                        <img :src="mediaProxy(m.media_url)" class="max-w-full max-h-64 rounded-md mb-1 object-contain bg-black/5" />
                                    </template>
                                    <div x-text="cleanBody(m.body)" class="text-sm leading-snug whitespace-pre-line break-words"></div>
                                    <div :class="m.direction === 'in' ? 'text-gray-400' : 'text-white/80'" class="text-[10px] mt-0.5 text-right leading-none">
                                        <span x-show="m.direction === 'out'" class="mr-1 uppercase tracking-wide" x-text="m.source === 'staff' ? 'staff' : 'bot'"></span>
                                        <span x-text="formatStampFull(msgTs(m))"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Composer --}}
                <div class="shrink-0 border-t border-gray-200 bg-white">
                    <div x-show="sendError" x-cloak class="px-3 pt-2 -mb-1">
                        <div class="text-xs text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-1.5 flex items-center justify-between gap-2">
                            <span x-text="sendError"></span>
                            <button @click="send()" class="text-rose-700 font-semibold hover:underline shrink-0">Cuba lagi</button>
                        </div>
                    </div>
                    <form @submit.prevent="send()" class="p-3 flex gap-2 items-end">
                        <textarea x-model="draft" rows="1" placeholder="Reply sebagai staff… (Enter = hantar, Shift+Enter = newline)" @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }" class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-1 focus:ring-emerald-500" style="max-height: 120px;"></textarea>
                        <button type="submit" :disabled="sending"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg px-4 py-2 shrink-0"
                            x-text="sending ? 'Menghantar…' : 'Hantar'"></button>
                    </form>
                </div>
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
        sending: false, sendError: '',
        filter: 'all',
        flag: {aiEnabled: true, humanTakeover: false, status: 'open', pinned: false},
        filters: [
            {key: 'all',      label: 'All'},
            {key: 'ai',       label: 'AI'},
            {key: 'takeover', label: 'Takeover'},
            {key: 'unread',   label: 'Unread'},
            {key: 'closed',   label: 'Closed'},
        ],
        async init() {
            await this.load();
            // Auto-select conversation from ?phone=... (came from Leads "Open chat")
            const params = new URLSearchParams(window.location.search);
            const wanted = params.get('phone');
            if (!wanted) return;
            const normalized = wanted.startsWith('+') ? wanted : '+' + wanted.replace(/^0+/, '60');
            let target = this.rows.find(c => c.phone === wanted)
                     || this.rows.find(c => c.phone === normalized)
                     || this.rows.find(c => (c.phone || '').replace(/\D/g, '').endsWith((wanted || '').replace(/\D/g, '').slice(-8)));
            if (!target) {
                // Not in the loaded 200 — synthesize a minimal row + let select() fetch messages
                target = {phone: normalized, name: null};
            }
            await this.select(target);
        },
        async load() {
            const url = new URL('/admin/whatsapp-agent/api/conversations', window.location.origin);
            if (this.q.trim()) url.searchParams.set('q', this.q.trim());
            url.searchParams.set('limit', '200');
            const r = await fetch(url, {credentials: 'same-origin'});
            const data = await r.json();
            this.rows = Array.isArray(data) ? data : (data.conversations || []);
        },
        countFor(key) {
            if (key === 'all') return this.rows.length;
            return this.rows.filter(c => this.matchFilter(c, key)).length;
        },
        matchFilter(c, key) {
            const f = c.flag || {};
            switch (key) {
                case 'ai':       return f.aiEnabled && !f.humanTakeover && f.status !== 'closed';
                case 'takeover': return f.humanTakeover;
                case 'unread':   return c.unread_count > 0;
                case 'closed':   return f.status === 'closed';
                default:         return true;
            }
        },
        filteredRows() {
            const base = this.filter === 'all' ? this.rows : this.rows.filter(c => this.matchFilter(c, this.filter));
            // Defensive: skip any nullish/malformed rows so Alpine's x-for doesn't
            // silently bail on a bad key or property access.
            return base.filter(c => c && typeof c === 'object');
        },
        async select(c) {
            this.selected = c;
            this.flag = c.flag || {aiEnabled: true, humanTakeover: false, status: 'open', pinned: false};
            const r = await fetch(`/admin/whatsapp-agent/api/conversations?phone=${encodeURIComponent(c.phone)}&limit=300`, {credentials: 'same-origin'});
            const data = await r.json();
            this.messages = data.messages || [];
            this.$nextTick(() => { this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight; });
        },
        async toggleFlag(field, value) {
            if (!this.selected) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`/admin/whatsapp-agent/api/flags/${encodeURIComponent(this.selected.phone)}`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({[field]: value}),
            });
            if (r.ok) {
                this.flag = {...this.flag, [field]: value};
                // Update selected row's flag in the list
                const idx = this.rows.findIndex(x => x.phone === this.selected.phone);
                if (idx >= 0) this.rows[idx].flag = {...(this.rows[idx].flag || {}), [field]: value};
            }
        },
        async deleteConversation() {
            if (!this.selected) return;
            if (!confirm(`Padam conversation dengan ${this.selected.name || this.selected.phone}? Semua mesej + media akan dihapus. Tak boleh undo.`)) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`/admin/whatsapp-agent/api/conversations/${encodeURIComponent(this.selected.phone)}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': csrfToken},
            });
            if (r.ok) {
                this.rows = this.rows.filter(c => c.phone !== this.selected.phone);
                this.selected = null;
                this.messages = [];
            }
        },
        async send() {
            if (!this.draft.trim() || !this.selected || this.sending) return;
            this.sending = true;
            this.sendError = '';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const text = this.draft;
            try {
                const r = await fetch('/admin/whatsapp-agent/api/send', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                    body: JSON.stringify({phone: this.selected.phone, message: text}),
                });
                if (r.ok) {
                    this.draft = '';           // clear only on confirmed success
                    const d = await r.json().catch(() => ({}));
                    if (d.via === 'wasender' && d.message) {
                        // Sent directly via WaSenderAPI — not in the bot thread yet,
                        // so append optimistically instead of reloading (which would drop it).
                        this.messages.push(d.message);
                        this.$nextTick(() => { this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight; });
                    } else {
                        await this.select(this.selected);   // bot recorded it — reload canonical thread
                    }
                } else {
                    // Keep the draft so staff don't lose their message; show why it failed.
                    let msg = `Gagal hantar (HTTP ${r.status}).`;
                    try { const d = await r.json(); if (d.error) msg = d.error; } catch (e) {}
                    this.sendError = msg;
                }
            } catch (e) {
                this.sendError = 'Gagal hantar — masalah rangkaian. Cuba lagi.';
            } finally {
                this.sending = false;
            }
        },
        bubbleClass(m) {
            if (m.direction === 'in') return 'bg-white text-gray-900 rounded-tl-none';
            return m.source === 'staff' ? 'bg-blue-500 text-white rounded-tr-none' : 'text-white rounded-tr-none';
        },
        bubbleStyle(m) {
            let s = 'width: fit-content; max-width: 65%;';
            if (m.direction === 'out' && m.source !== 'staff') s += ' background-color: #005c4b;';
            return s;
        },
        mediaProxy(botPath) {
            return botPath.replace(/^\/admin\/api\/media\//, '/admin/whatsapp-agent/api/media/');
        },
        msgTs(m) {
            return m.timestamp || m.ts || m.created_at || m.received_at || m.sent_at || null;
        },
        cleanBody(s) {
            if (!s) return '';
            return String(s)
                .replace(/\r\n?/g, '\n')
                .replace(/[​-‍﻿ ]/g, ' ')
                .replace(/[ \t]+$/gm, '')                    // strip trailing spaces on each line
                .trim()
                .replace(/\n{3,}/g, '\n\n');                 // collapse 3+ blank lines to 1 blank
        },
        parseTs(v) {
            if (!v) return null;
            if (typeof v === 'string') {
                // Match "YYYY-MM-DD HH:MM(:SS)(.ffff)" optionally with a T separator
                // and an optional trailing timezone (Z or ±HH:MM).
                const m = v.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?(?:\.\d+)?(Z|[+-]\d{2}:?\d{2})?$/);
                if (m) {
                    if (m[7]) {
                        // Explicit offset/Z (e.g. our WaSender fallback) — parse as absolute.
                        const d = new Date(v.replace(' ', 'T'));
                        return isNaN(d.getTime()) ? null : d;
                    }
                    // Naive timestamp from the Python bot = Malaysia wall-clock (UTC+8).
                    // Build the matching absolute instant so MYT display shows it verbatim.
                    const ms = Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +(m[6] || 0)) - 8 * 3600 * 1000;
                    return new Date(ms);
                }
            }
            if (typeof v === 'number' && v < 1e12) v = v * 1000;
            const d = new Date(v);
            return isNaN(d.getTime()) ? null : d;
        },
        formatTime(v) {
            const d = this.parseTs(v);
            if (!d) return '';
            return d.toLocaleTimeString('ms-MY', {hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Kuala_Lumpur'});
        },
        // Calendar date (YYYY-MM-DD) in Malaysia time, independent of the device TZ.
        myDay(d) {
            return d.toLocaleDateString('en-CA', {timeZone: 'Asia/Kuala_Lumpur'});
        },
        // Full date + time in Malaysia time, e.g. "07/07/26, 13:57" — used on message bubbles.
        formatStampFull(v) {
            const d = this.parseTs(v);
            if (!d) return '';
            return d.toLocaleString('ms-MY', {day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Kuala_Lumpur'});
        },
        // Smart stamp for the conversation list: time if today, "Semalam HH:MM" if
        // yesterday, else "DD/MM/YY HH:MM" — so the date shows once it isn't today.
        formatStamp(v) {
            try {
                const d = this.parseTs(v);
                if (!d) return '';
                const time = this.formatTime(v);
                const now = new Date();
                const day = this.myDay(d);
                if (day === this.myDay(now)) return time;
                if (day === this.myDay(new Date(now.getTime() - 86400000))) return 'Semalam ' + time;
                return d.toLocaleDateString('ms-MY', {day: '2-digit', month: '2-digit', year: '2-digit', timeZone: 'Asia/Kuala_Lumpur'}) + ' ' + time;
            } catch (e) { return ''; }
        },
        formatDate(v) {
            const d = this.parseTs(v);
            if (!d) return '';
            const now = new Date();
            const today = this.myDay(now);
            const yesterday = this.myDay(new Date(now.getTime() - 86400000));
            const day = this.myDay(d);
            if (day === today) return 'Hari ini';
            if (day === yesterday) return 'Semalam';
            return d.toLocaleDateString('ms-MY', {day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Kuala_Lumpur'});
        },
        showDateSeparator(i) {
            if (i === 0) return true;
            const prev = this.parseTs(this.msgTs(this.messages[i - 1]));
            const curr = this.parseTs(this.msgTs(this.messages[i]));
            if (!prev || !curr) return false;
            return this.myDay(prev) !== this.myDay(curr);
        },
        initial(s) {
            if (!s) return '?';
            const t = s.trim().replace(/^\+?60/, '');
            return t.charAt(0).toUpperCase();
        },
        tierColor(t) {
            return t === 'hot' ? 'bg-rose-100 text-rose-700' :
                   t === 'warm' ? 'bg-amber-100 text-amber-700' :
                   t === 'new_lead' ? 'bg-blue-100 text-blue-700' :
                   'bg-gray-100 text-gray-600';
        },
        stageColor(s) {
            return s === 'appointment_booked' ? 'bg-emerald-100 text-emerald-700' :
                   s === 'appointment_offered' ? 'bg-sky-100 text-sky-700' :
                   s === 'qualified' ? 'bg-indigo-100 text-indigo-700' :
                   s === 'contacted' ? 'bg-purple-100 text-purple-700' :
                   s === 'not_interested' ? 'bg-gray-100 text-gray-500' :
                   'bg-slate-100 text-slate-700';
        },
    };
}
</script>
@endpush
@endsection
