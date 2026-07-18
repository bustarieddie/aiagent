@extends('layouts.admin')
@section('title', 'Leads')
@section('content')
<div class="p-6 space-y-4" x-data="leadsPage()" x-init="load()">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Leads</h2>
            <p class="text-sm text-gray-500">Pesakit & lead daripada Python bot.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg px-2 py-1">
                <span class="text-xs text-gray-500">Dari</span>
                <input type="date" x-model="fromDate" class="text-xs border-0 focus:outline-none" />
                <span class="text-xs text-gray-500">ke</span>
                <input type="date" x-model="toDate" class="text-xs border-0 focus:outline-none" />
            </div>
            <button @click="startDistribute()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg px-4 py-1.5 flex items-center gap-1.5">
                🔀 <span>Bahagikan Leads</span>
            </button>
            <button @click="startAutoClassify(false)" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg px-4 py-1.5 flex items-center gap-1.5">
                🤖 <span>Auto-Classify Services</span>
            </button>
            <button @click="exportCsv()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-1.5 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    {{-- Auto-classify progress modal --}}
    <div x-show="classifyOpen" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="classifyOpen = false">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-900">🤖 AI Classifying Leads</h3>
                <button @click="classifyOpen = false; classifyCancel = true" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <p class="text-sm text-gray-600">
                <span class="font-medium text-gray-900" x-text="classifyDone"></span>
                / <span x-text="classifyTotal"></span> selesai
                <span x-show="classifyDone && classifyTotal" class="text-gray-400">
                    (<span x-text="Math.round((classifyDone/Math.max(1,classifyTotal))*100)"></span>%)
                </span>
            </p>
            <div class="mt-3 bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="bg-purple-500 h-full transition-all duration-300" :style="`width: ${(classifyDone/Math.max(1,classifyTotal)*100)||0}%`"></div>
            </div>
            <div x-show="classifyCurrent" class="mt-3 text-xs text-gray-500 font-mono">
                Sedang scan: <span x-text="classifyCurrent"></span>
            </div>
            <div x-show="classifyLastResult" class="mt-2 text-xs text-gray-600 border-t border-gray-100 pt-2" x-html="classifyLastResult"></div>
            <div x-show="classifyDone === classifyTotal && classifyTotal > 0" class="mt-3 text-sm text-emerald-700 font-medium">
                ✓ Siap! <span x-text="classifyHits"></span> lead di-classify, <span x-text="classifyMisses"></span> tak cukup info.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 flex flex-wrap gap-2">
        <select x-model="tier" @change="load()" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5">
            <option value="">Semua tier</option>
            <option value="hot">hot</option><option value="warm">warm</option>
            <option value="new_lead">new_lead</option><option value="cold">cold</option>
        </select>
        <select x-model="stage" @change="load()" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5">
            <option value="">Semua stage</option>
            <option value="new_lead">new_lead</option><option value="contacted">contacted</option>
            <option value="qualified">qualified</option><option value="appointment_offered">appointment_offered</option>
            <option value="appointment_booked">appointment_booked</option><option value="not_interested">not_interested</option>
        </select>
        <select x-model="service" @change="load()" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5">
            <option value="">Semua servis</option>
            <option value="khatan">Khatan</option><option value="minor_surgery">Minor Surgery</option>
            <option value="knee_pain">Lutut</option><option value="diabetes">Diabetes</option>
        </select>
        <select x-model="assignedFilter" @change="load()" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5">
            <option value="">Semua staff</option>
            <option value="0">Belum ditugaskan</option>
            <template x-for="s in staffList" :key="s.id">
                <option :value="s.id" x-text="s.name"></option>
            </template>
        </select>
        <input x-model="q" @keyup.enter="load()" placeholder="Cari nama atau nombor…" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 flex-1 min-w-[200px]" />
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-3 py-2 font-medium">Nama / Phone</th>
                    <th class="px-3 py-2 font-medium">Tier</th>
                    <th class="px-3 py-2 font-medium">Stage</th>
                    <th class="px-3 py-2 font-medium">Servis</th>
                    <th class="px-3 py-2 font-medium">Ditugaskan</th>
                    <th class="px-3 py-2 font-medium">Score</th>
                    <th class="px-3 py-2 font-medium">Last message</th>
                    <th class="px-3 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="l in rows" :key="l.phone">
                    <tr>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900" x-text="l.name || '—'"></div>
                            <div class="text-xs text-gray-500 font-mono" x-text="l.phone"></div>
                            <div class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-1">
                                <span>🕐</span><span x-text="fmtWhen(l)"></span>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <span :class="tierColor(l.lead_tier)" class="text-xs px-2 py-0.5 rounded-full" x-text="l.lead_tier || '—'"></span>
                        </td>
                        <td class="px-3 py-2">
                            <select @change="updateField(l, 'crm_stage', $event.target.value)" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
                                <template x-for="s in ['new_lead','contacted','qualified','appointment_offered','appointment_booked','not_interested']" :key="s">
                                    <option :value="s" :selected="s === l.crm_stage" x-text="s"></option>
                                </template>
                            </select>
                        </td>
                        <td class="px-3 py-2">
                            <select @change="updateField(l, 'service_interested', $event.target.value)" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
                                <option value="">—</option>
                                <option value="khatan" :selected="l.service_interested === 'khatan'">Khatan</option>
                                <option value="minor_surgery" :selected="l.service_interested === 'minor_surgery'">Minor Surgery</option>
                                <option value="knee_pain" :selected="l.service_interested === 'knee_pain'">Lutut</option>
                                <option value="diabetes" :selected="l.service_interested === 'diabetes'">Diabetes</option>
                            </select>
                        </td>
                        <td class="px-3 py-2">
                            <select @change="assignLead(l, $event.target.value)" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
                                <option value="" :selected="!l.assigned_to">—</option>
                                <template x-for="s in staffList" :key="s.id">
                                    <option :value="s.id" :selected="l.assigned_to?.id === s.id" x-text="s.name"></option>
                                </template>
                            </select>
                        </td>
                        <td class="px-3 py-2 text-xs" x-text="l.lead_score ?? '—'"></td>
                        <td class="px-3 py-2 text-xs text-gray-600 max-w-xs truncate" x-text="l.last_message ?? '—'"></td>
                        <td class="px-3 py-2 text-right">
                            <a :href="`/admin/whatsapp-agent/conversations?phone=${encodeURIComponent(l.phone)}`" class="text-emerald-700 hover:underline text-xs">Open chat →</a>
                        </td>
                    </tr>
                </template>
                <tr x-show="!rows.length">
                    <td colspan="8" class="px-3 py-6 text-center text-gray-400">Tiada lead.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function leadsPage() {
    return {
        rows: [], q: '', tier: '', stage: '', service: '',
        fromDate: '', toDate: '',
        assignedFilter: '', staffList: [],
        classifyOpen: false, classifyDone: 0, classifyTotal: 0,
        classifyCurrent: '', classifyLastResult: '',
        classifyHits: 0, classifyMisses: 0, classifyCancel: false,
        async load() {
            const url = new URL('/admin/whatsapp-agent/api/leads', window.location.origin);
            if (this.q) url.searchParams.set('q', this.q);
            if (this.tier) url.searchParams.set('tier', this.tier);
            if (this.stage) url.searchParams.set('stage', this.stage);
            if (this.service) url.searchParams.set('service', this.service);
            if (this.assignedFilter !== '') url.searchParams.set('assigned_to', this.assignedFilter);
            url.searchParams.set('limit', '200');
            const r = await fetch(url, {credentials: 'same-origin'});
            const d = await r.json();
            this.rows = this.sortByRecent(d.leads || []);
            if (!this.staffList.length) await this.loadStaff();
        },
        // Always show newest activity first, and keep it that way on every load.
        sortByRecent(leads) {
            return [...leads].sort((a, b) => this.leadTs(b) - this.leadTs(a));
        },
        // Parse a lead's most recent timestamp (naive strings are Malaysia time).
        parseMY(v) {
            if (!v) return null;
            if (typeof v === 'number') return new Date(v < 1e12 ? v * 1000 : v);
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
            if (m) return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +(m[6] || 0)) - 8 * 3600 * 1000);
            const t = Date.parse(v);
            return isNaN(t) ? null : new Date(t);
        },
        recentValue(l) {
            return l.last_interaction || l.last_ts || l.updated_at || l.last_message_at || l.last_message_ts || l.created_at || l.first_seen || null;
        },
        leadTs(l) {
            const d = this.parseMY(this.recentValue(l));
            return d ? d.getTime() : 0;
        },
        fmtWhen(l) {
            const d = this.parseMY(this.recentValue(l));
            if (!d) return '—';
            return d.toLocaleString('ms-MY', {day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Kuala_Lumpur'});
        },
        async loadStaff() {
            const r = await fetch('/admin/whatsapp-agent/api/staff', {credentials: 'same-origin'});
            const d = await r.json();
            this.staffList = (d.staff || []).filter(s => s.is_active);
        },
        async startDistribute() {
            if (!this.staffList.length) {
                if (!confirm('Belum ada staff aktif. Ke halaman Staff untuk tambah dulu?')) return;
                window.location = '/admin/whatsapp-agent/staff';
                return;
            }
            const scope = confirm(
                'Bahagikan leads secara bergilir (round-robin)?\n\n' +
                'OK  = agih hanya yang belum ditugaskan\n' +
                'Cancel = agih SEMUA (overwrite yang sedia ada)'
            ) ? 'unassigned' : 'all';
            const override = scope === 'all';
            if (scope === 'all' && !confirm('Overwrite ALL assignments? Semua leads akan di-rotate semula.')) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const filter = {tier: this.tier, stage: this.stage, service: this.service};
            const r = await fetch('/admin/whatsapp-agent/api/leads/distribute', {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({scope, override, filter}),
            });
            const res = await r.json();
            const perStaff = Object.entries(res.per_staff || {})
                .map(([id, n]) => {
                    const s = this.staffList.find(x => String(x.id) === String(id));
                    return `${s ? s.name : '#'+id}: ${n}`;
                }).join('\n');
            alert(`Siap!\n\nAssigned: ${res.assigned}\nSkipped: ${res.skipped}\n\n${perStaff}`);
            await this.load();
            await this.loadStaff();
        },
        async assignLead(l, staffId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`/admin/whatsapp-agent/api/leads/${encodeURIComponent(l.phone)}/assign`, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({staff_member_id: staffId ? Number(staffId) : null}),
            });
            if (r.ok) {
                const s = this.staffList.find(x => String(x.id) === String(staffId));
                l.assigned_to = s ? {id: s.id, name: s.name, method: 'manual'} : null;
                await this.loadStaff();
            }
        },
        exportCsv() {
            const url = new URL('/admin/whatsapp-agent/api/leads/export', window.location.origin);
            if (this.q) url.searchParams.set('q', this.q);
            if (this.tier) url.searchParams.set('tier', this.tier);
            if (this.stage) url.searchParams.set('stage', this.stage);
            if (this.service) url.searchParams.set('service', this.service);
            if (this.fromDate) url.searchParams.set('from', this.fromDate);
            if (this.toDate) url.searchParams.set('to', this.toDate);
            window.location = url.toString();
        },
        async startAutoClassify(force) {
            const overwrite = force || confirm(
                'Auto-classify servis untuk lead yang belum ada servis?\n\n' +
                'OK  = classify yang kosong sahaja\n' +
                'Cancel = classify SEMUA (overwrite yang sedia ada)'
            );
            const url = new URL('/admin/whatsapp-agent/api/leads/classifiable', window.location.origin);
            if (!overwrite) url.searchParams.set('force', '1');
            const r = await fetch(url, {credentials: 'same-origin'});
            const {phones, total} = await r.json();

            if (!total) {
                alert('Tak ada lead untuk classify.');
                return;
            }

            this.classifyOpen = true;
            this.classifyTotal = total;
            this.classifyDone = 0;
            this.classifyHits = 0;
            this.classifyMisses = 0;
            this.classifyCurrent = '';
            this.classifyLastResult = '';
            this.classifyCancel = false;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            for (const phone of phones) {
                if (this.classifyCancel) break;
                this.classifyCurrent = phone;
                try {
                    const cr = await fetch(`/admin/whatsapp-agent/api/leads/${encodeURIComponent(phone)}/classify`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'X-CSRF-TOKEN': csrfToken},
                    });
                    const res = await cr.json();
                    if (res.ok) {
                        this.classifyHits++;
                        this.classifyLastResult = `<span class="text-emerald-700">✓ ${phone}</span> → <b>${res.service}</b> <span class="text-gray-400">(${res.confidence})</span> — ${res.reason || ''}`;
                        // Update row locally
                        const idx = this.rows.findIndex(r => r.phone === phone);
                        if (idx >= 0) this.rows[idx].service_interested = res.service;
                    } else {
                        this.classifyMisses++;
                        this.classifyLastResult = `<span class="text-gray-400">— ${phone}</span> tak diclassify (${res.reason || 'no info'})`;
                    }
                } catch (e) {
                    this.classifyMisses++;
                    this.classifyLastResult = `<span class="text-rose-600">✗ ${phone}</span> error`;
                }
                this.classifyDone++;
            }
            this.classifyCurrent = '';
        },
        async updateField(l, field, value) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`/admin/whatsapp-agent/api/leads/${encodeURIComponent(l.phone)}`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({[field]: value}),
            });
            if (r.ok) {
                l[field] = value;   // reflect locally so it persists on this view
            } else {
                alert('Gagal simpan perubahan. Cuba lagi.');
            }
        },
        tierColor(t) {
            return t === 'hot' ? 'bg-rose-100 text-rose-700' :
                   t === 'warm' ? 'bg-amber-100 text-amber-700' :
                   t === 'new_lead' ? 'bg-blue-100 text-blue-700' :
                   'bg-gray-100 text-gray-500';
        },
    };
}
</script>
@endpush
@endsection
