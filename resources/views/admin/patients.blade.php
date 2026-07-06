@extends('layouts.admin')
@section('title', 'Patients')
@section('content')
<div class="p-6 space-y-4" x-data="patientsPage()" x-init="load()">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Patients</h2>
            <p class="text-sm text-gray-500">CRM penuh dengan rekod klinikal. Total: <span x-text="total"></span> pesakit.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 flex flex-wrap gap-2 items-end">
        <input x-model="q" @keyup.enter="load()" placeholder="Cari nama atau nombor…" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 flex-1 min-w-[200px]" />
        <select x-model="tier" @change="load()" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5">
            <option value="">Semua tier</option>
            <option value="hot">hot</option><option value="warm">warm</option>
            <option value="new_lead">new_lead</option><option value="cold">cold</option>
        </select>
        <select x-model="stage" @change="load()" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5">
            <option value="">Semua stage</option>
            <option value="new_lead">new_lead</option><option value="contacted">contacted</option>
            <option value="qualified">qualified</option><option value="appointment_offered">appointment_offered</option>
            <option value="appointment_booked">appointment_booked</option>
            <option value="completed">completed</option><option value="not_interested">not_interested</option>
        </select>
        <select x-model="service" @change="load()" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5">
            <option value="">Semua servis</option>
            <option value="khatan">Khatan</option><option value="minor_surgery">Minor Surgery</option>
            <option value="knee_pain">Lutut</option><option value="diabetes">Diabetes</option>
        </select>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-3 py-2 font-medium">Nama / Phone</th>
                    <th class="px-3 py-2 font-medium">Umur</th>
                    <th class="px-3 py-2 font-medium">Servis</th>
                    <th class="px-3 py-2 font-medium">Stage</th>
                    <th class="px-3 py-2 font-medium">Tier</th>
                    <th class="px-3 py-2 font-medium">Consent</th>
                    <th class="px-3 py-2 font-medium">Updated</th>
                    <th class="px-3 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="p in rows" :key="p.phone">
                    <tr>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900" x-text="p.name || '—'"></div>
                            <div class="text-xs text-gray-500 font-mono" x-text="p.phone"></div>
                        </td>
                        <td class="px-3 py-2 text-xs" x-text="p.age || '—'"></td>
                        <td class="px-3 py-2">
                            <select @change="patchField(p, 'service_interested', $event.target.value)" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
                                <option value="">—</option>
                                <template x-for="s in [['khatan','Khatan'],['minor_surgery','Minor Surgery'],['knee_pain','Lutut'],['diabetes','Diabetes']]" :key="s[0]">
                                    <option :value="s[0]" :selected="s[0] === p.service_interested" x-text="s[1]"></option>
                                </template>
                            </select>
                        </td>
                        <td class="px-3 py-2">
                            <select @change="patchField(p, 'crm_stage', $event.target.value)" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
                                <option value="">—</option>
                                <template x-for="s in ['new_lead','contacted','qualified','appointment_offered','appointment_booked','completed','not_interested']" :key="s">
                                    <option :value="s" :selected="s === p.crm_stage" x-text="s"></option>
                                </template>
                            </select>
                        </td>
                        <td class="px-3 py-2">
                            <select @change="patchField(p, 'lead_tier', $event.target.value)" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
                                <option value="">—</option>
                                <template x-for="t in ['hot','warm','new_lead','cold']" :key="t">
                                    <option :value="t" :selected="t === p.lead_tier" x-text="t"></option>
                                </template>
                            </select>
                        </td>
                        <td class="px-3 py-2 text-xs" x-text="p.consent_marketing || '—'"></td>
                        <td class="px-3 py-2 text-xs text-gray-500" x-text="formatTime(p.updated_at)"></td>
                        <td class="px-3 py-2 text-right">
                            <a :href="`/admin/whatsapp-agent/patients/${encodeURIComponent(p.phone)}`" class="text-emerald-700 hover:underline text-xs">Detail →</a>
                        </td>
                    </tr>
                </template>
                <tr x-show="!rows.length">
                    <td colspan="8" class="px-3 py-6 text-center text-gray-400">Tiada pesakit.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function patientsPage() {
    return {
        rows: [], total: 0, q: '', tier: '', stage: '', service: '',
        async load() {
            const url = new URL('/admin/whatsapp-agent/api/patients', window.location.origin);
            if (this.q) url.searchParams.set('q', this.q);
            if (this.tier) url.searchParams.set('tier', this.tier);
            if (this.stage) url.searchParams.set('stage', this.stage);
            if (this.service) url.searchParams.set('service', this.service);
            url.searchParams.set('limit', '50');
            const r = await fetch(url, {credentials: 'same-origin'});
            const d = await r.json();
            this.rows = d.patients || [];
            this.total = d.total || 0;
        },
        async patchField(p, field, value) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            p[field] = value || null;
            await fetch(`/admin/whatsapp-agent/api/patients/${encodeURIComponent(p.phone)}`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({[field]: value || null}),
            });
        },
        formatTime(iso) {
            if (!iso) return '—';
            try {
                return new Date(iso).toLocaleString('ms-MY', {day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'});
            } catch { return '—'; }
        },
    };
}
</script>
@endpush
@endsection
