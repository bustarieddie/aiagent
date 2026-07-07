@extends('layouts.admin')
@section('title', 'Panels')
@section('content')
<div class="p-6 space-y-4 max-w-5xl" x-data="panelsPage()" x-init="load()">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">🏥 Panel Insurance / Korporat</h2>
            <p class="text-sm text-gray-500">Senarai panel yang diterima Klinik Bustari. Bot rujuk senarai ni untuk jawab soalan "ada panel X?".</p>
        </div>
        <button @click="openAdd()" class="text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-4 py-2 shrink-0">+ Tambah Panel</button>
    </div>

    {{-- Stats + search --}}
    <div class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-2 text-sm">
            <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 rounded-full px-3 py-1 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span><b x-text="stats.active"></b> Active
            </span>
            <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-500 rounded-full px-3 py-1 font-medium">
                <span class="w-2 h-2 rounded-full bg-gray-400"></span><b x-text="stats.inactive"></b> Inactive
            </span>
            <span class="text-gray-400">·</span>
            <span class="text-gray-500"><b x-text="stats.total"></b> jumlah</span>
        </div>
        <div class="flex-1 min-w-[200px]"></div>
        <input x-model="q" @input.debounce.300ms="load()" placeholder="Cari nama atau kod panel…"
               class="w-72 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-emerald-500" />
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left font-semibold px-4 py-2.5">Nama Panel</th>
                    <th class="text-left font-semibold px-4 py-2.5 w-48">Kod</th>
                    <th class="text-left font-semibold px-4 py-2.5 w-32">Status</th>
                    <th class="text-right font-semibold px-4 py-2.5 w-32">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="p in panels" :key="p.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-medium text-gray-900" x-text="p.name"></td>
                        <td class="px-4 py-2.5 text-gray-500 font-mono text-xs" x-text="p.code"></td>
                        <td class="px-4 py-2.5">
                            <button @click="toggle(p)"
                                :class="p.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold">
                                <span :class="p.is_active ? 'bg-emerald-500' : 'bg-gray-400'" class="w-2 h-2 rounded-full"></span>
                                <span x-text="p.is_active ? 'Active' : 'Inactive'"></span>
                            </button>
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            <button @click="openEdit(p)" class="text-xs text-gray-500 hover:text-gray-800 hover:underline">Edit</button>
                            <span class="text-gray-300 mx-1">·</span>
                            <button @click="remove(p)" class="text-xs text-rose-500 hover:text-rose-700 hover:underline">Padam</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="!panels.length" class="p-8 text-center text-sm text-gray-400">
            <span x-show="q">Tiada panel padan "<span x-text="q"></span>".</span>
            <span x-show="!q">Belum ada panel. Run <code class="bg-gray-100 px-1 py-0.5 rounded">php artisan panels:seed</code> atau tambah manual.</span>
        </div>
    </div>

    <p class="text-xs text-gray-500 bg-amber-50 border border-amber-200 rounded-lg p-3">
        <b>Nota:</b> Setiap kali panel di-toggle / tambah / edit, knowledge entry <code class="bg-white/60 px-1 rounded">insurance_panels</code> di-update automatik supaya bot boleh jawab soalan panel tanpa escalate ke staff.
    </p>

    {{-- Add/Edit modal --}}
    <div x-show="formOpen" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="formOpen = false">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editing ? 'Edit Panel' : 'Tambah Panel'"></h3>
                <button @click="formOpen = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <div class="px-6 py-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Panel</label>
                    <input type="text" x-model="form.name"
                        class="w-full text-sm rounded-lg border border-gray-200 px-3 py-2 focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kod / Panel ID</label>
                    <input type="text" x-model="form.code"
                        class="w-full text-sm rounded-lg border border-gray-200 px-3 py-2 focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
                </div>
                <label class="flex items-center justify-between gap-4 cursor-pointer pt-1">
                    <span class="text-sm text-gray-700">Active</span>
                    <button type="button" @click="form.is_active = !form.is_active"
                        :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300'"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0">
                        <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                              class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                    </button>
                </label>
                <p x-show="formError" x-cloak class="text-xs text-rose-600" x-text="formError"></p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100">
                <button @click="formOpen = false" class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-50">Batal</button>
                <button @click="save()" :disabled="saving"
                    class="text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 px-4 py-2 rounded-lg"
                    x-text="saving ? 'Menyimpan…' : 'Simpan'"></button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function panelsPage() {
    return {
        panels: [], q: '', stats: {total: 0, active: 0, inactive: 0},
        formOpen: false, editing: null, form: {name: '', code: '', is_active: true},
        saving: false, formError: '',
        csrf() { return document.querySelector('meta[name="csrf-token"]').content; },
        async load() {
            const url = new URL('/admin/whatsapp-agent/api/panels', window.location.origin);
            if (this.q.trim()) url.searchParams.set('q', this.q.trim());
            const r = await fetch(url, {credentials: 'same-origin'});
            const d = await r.json();
            this.panels = d.panels || [];
            this.stats = {total: d.total || 0, active: d.active || 0, inactive: d.inactive || 0};
        },
        async toggle(p) {
            const r = await fetch(`/admin/whatsapp-agent/api/panels/${p.id}/toggle`, {
                method: 'POST', credentials: 'same-origin', headers: {'X-CSRF-TOKEN': this.csrf()},
            });
            if (r.ok) {
                const wasActive = p.is_active;
                p.is_active = !p.is_active;
                this.stats.active += p.is_active ? 1 : -1;
                this.stats.inactive += p.is_active ? -1 : 1;
            }
        },
        openAdd() {
            this.editing = null;
            this.form = {name: '', code: '', is_active: true};
            this.formError = '';
            this.formOpen = true;
        },
        openEdit(p) {
            this.editing = p;
            this.form = {name: p.name, code: p.code, is_active: p.is_active};
            this.formError = '';
            this.formOpen = true;
        },
        async save() {
            if (!this.form.name.trim() || !this.form.code.trim()) {
                this.formError = 'Nama dan kod wajib diisi.';
                return;
            }
            this.saving = true;
            this.formError = '';
            try {
                const isEdit = !!this.editing;
                const url = isEdit
                    ? `/admin/whatsapp-agent/api/panels/${this.editing.id}`
                    : '/admin/whatsapp-agent/api/panels';
                const r = await fetch(url, {
                    method: isEdit ? 'PATCH' : 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf()},
                    body: JSON.stringify(this.form),
                });
                if (r.ok) {
                    this.formOpen = false;
                    await this.load();
                } else if (r.status === 422) {
                    const d = await r.json().catch(() => ({}));
                    this.formError = d.errors?.code?.[0] || d.message || 'Kod panel sudah wujud.';
                } else {
                    this.formError = 'Gagal simpan panel.';
                }
            } finally {
                this.saving = false;
            }
        },
        async remove(p) {
            if (!confirm(`Padam panel "${p.name}"?`)) return;
            const r = await fetch(`/admin/whatsapp-agent/api/panels/${p.id}`, {
                method: 'DELETE', credentials: 'same-origin', headers: {'X-CSRF-TOKEN': this.csrf()},
            });
            if (r.ok) await this.load();
        },
    };
}
</script>
@endpush
@endsection
