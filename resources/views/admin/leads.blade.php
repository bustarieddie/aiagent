@extends('layouts.admin')
@section('title', 'Leads')
@section('content')
<div class="p-6 space-y-4" x-data="leadsPage()" x-init="load()">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Leads</h2>
        <p class="text-sm text-gray-500">Pesakit & lead daripada Python bot.</p>
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
                        </td>
                        <td class="px-3 py-2">
                            <span :class="tierColor(l.lead_tier)" class="text-xs px-2 py-0.5 rounded-full" x-text="l.lead_tier || '—'"></span>
                        </td>
                        <td class="px-3 py-2">
                            <select @change="updateField(l, 'stage', $event.target.value)" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
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
                        <td class="px-3 py-2 text-xs" x-text="l.lead_score ?? '—'"></td>
                        <td class="px-3 py-2 text-xs text-gray-600 max-w-xs truncate" x-text="l.last_message ?? '—'"></td>
                        <td class="px-3 py-2 text-right">
                            <a :href="`/admin/whatsapp-agent/conversations?phone=${encodeURIComponent(l.phone)}`" class="text-emerald-700 hover:underline text-xs">Open chat →</a>
                        </td>
                    </tr>
                </template>
                <tr x-show="!rows.length">
                    <td colspan="7" class="px-3 py-6 text-center text-gray-400">Tiada lead.</td>
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
        async load() {
            const url = new URL('/admin/whatsapp-agent/api/leads', window.location.origin);
            if (this.q) url.searchParams.set('q', this.q);
            if (this.tier) url.searchParams.set('tier', this.tier);
            if (this.stage) url.searchParams.set('stage', this.stage);
            if (this.service) url.searchParams.set('service', this.service);
            url.searchParams.set('limit', '200');
            const r = await fetch(url, {credentials: 'same-origin'});
            const d = await r.json();
            this.rows = d.leads || [];
        },
        async updateField(l, field, value) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            await fetch(`/admin/whatsapp-agent/api/leads/${encodeURIComponent(l.phone)}`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({[field === 'stage' ? 'stage' : field]: value}),
            });
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
