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
                            <div class="flex items-center gap-1.5">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900 truncate" x-text="p.name || '—'"></div>
                                    <div class="text-xs text-gray-500 font-mono" x-text="p.phone"></div>
                                </div>
                                <button @click="openEdit(p)"
                                    :class="!p.name ? 'bg-amber-100 text-amber-700 hover:bg-amber-200 ring-1 ring-amber-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                    class="text-xs px-2 py-1 rounded-md font-medium shrink-0"
                                    :title="p.name ? 'Edit details' : 'Tiada nama — klik untuk isi'">
                                    <span x-show="!p.name">+ Nama</span>
                                    <span x-show="p.name">✎</span>
                                </button>
                            </div>
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

    {{-- Editor modal --}}
    <div x-show="editorOpen" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="editorOpen = false">
        <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-5 py-3 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Edit Pesakit</h3>
                <button @click="editorOpen = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
            </div>
            <div class="p-5 space-y-3">
                <div class="text-xs text-gray-500 font-mono bg-gray-50 rounded-lg px-3 py-2" x-text="draft.phone"></div>

                <label class="block text-sm">
                    <span class="text-gray-700 font-medium">Nama penuh</span>
                    <input type="text" x-model="draft.name" placeholder="Cth: Ahmad bin Ali"
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-sm">
                        <span class="text-gray-700">Umur</span>
                        <input type="number" x-model.number="draft.age" min="0" max="120"
                            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </label>
                    <label class="block text-sm">
                        <span class="text-gray-700">Jantina</span>
                        <select x-model="draft.gender" class="mt-1 w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                            <option value="">—</option>
                            <option value="L">Lelaki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </label>
                </div>

                <label class="block text-sm">
                    <span class="text-gray-700">IC / MyKad</span>
                    <input type="text" x-model="draft.ic_number" placeholder="900101-01-1234"
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono" />
                </label>

                <label class="block text-sm">
                    <span class="text-gray-700">Alamat</span>
                    <textarea x-model="draft.address" rows="2"
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-sm">
                        <span class="text-gray-700">Servis</span>
                        <select x-model="draft.service_interested" class="mt-1 w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                            <option value="">—</option>
                            <option value="khatan">Khatan</option>
                            <option value="minor_surgery">Minor Surgery</option>
                            <option value="knee_pain">Lutut</option>
                            <option value="diabetes">Diabetes</option>
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="text-gray-700">Consent Marketing</span>
                        <select x-model="draft.consent_marketing" class="mt-1 w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                            <option value="">—</option>
                            <option value="yes">yes</option>
                            <option value="no">no</option>
                        </select>
                    </label>
                </div>

                <label class="block text-sm">
                    <span class="text-gray-700">Notes</span>
                    <textarea x-model="draft.notes" rows="3" placeholder="Nota klinikal, allergy, dll…"
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </label>
            </div>
            <div class="sticky bottom-0 bg-white border-t border-gray-100 px-5 py-3 flex justify-end gap-2">
                <button @click="editorOpen = false" class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2">Batal</button>
                <button @click="saveEdit()" :disabled="saving"
                    :class="saving ? 'bg-gray-300' : 'bg-emerald-600 hover:bg-emerald-700'"
                    class="text-white text-sm font-medium rounded-lg px-5 py-2">
                    <span x-show="!saving">Simpan</span>
                    <span x-show="saving">Menyimpan…</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function patientsPage() {
    return {
        rows: [], total: 0, q: '', tier: '', stage: '', service: '',
        editorOpen: false, saving: false, draft: {},
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
        openEdit(p) {
            this.draft = {
                phone: p.phone,
                name: p.name || '',
                age: p.age || null,
                gender: p.gender || '',
                ic_number: p.ic_number || '',
                address: p.address || '',
                service_interested: p.service_interested || '',
                consent_marketing: p.consent_marketing || '',
                notes: p.notes || '',
            };
            this.editorOpen = true;
        },
        async saveEdit() {
            if (!this.draft.phone) return;
            this.saving = true;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const payload = {
                name: this.draft.name?.trim() || null,
                age: this.draft.age || null,
                gender: this.draft.gender || null,
                ic_number: this.draft.ic_number?.trim() || null,
                address: this.draft.address?.trim() || null,
                service_interested: this.draft.service_interested || null,
                consent_marketing: this.draft.consent_marketing || null,
                notes: this.draft.notes?.trim() || null,
            };
            try {
                const r = await fetch(`/admin/whatsapp-agent/api/patients/${encodeURIComponent(this.draft.phone)}`, {
                    method: 'PATCH', credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                    body: JSON.stringify(payload),
                });
                if (r.ok) {
                    // Update row in-place
                    const idx = this.rows.findIndex(x => x.phone === this.draft.phone);
                    if (idx >= 0) {
                        Object.assign(this.rows[idx], payload);
                    }
                    this.editorOpen = false;
                } else {
                    const err = await r.json().catch(() => ({}));
                    alert('Gagal simpan: ' + (err.message || r.statusText));
                }
            } finally {
                this.saving = false;
            }
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
