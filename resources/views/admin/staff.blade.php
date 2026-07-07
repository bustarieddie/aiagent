@extends('layouts.admin')
@section('title', 'Staff')
@section('content')
<div class="p-6 space-y-4 max-w-4xl" x-data="staffPage()" x-init="load()">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">🧑‍💼 Staff</h2>
            <p class="text-sm text-gray-500">Ahli staf yang boleh terima assignment leads. Round-robin ikut senarai aktif.</p>
        </div>
        <button @click="openNew()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-1.5">+ Tambah Staff</button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-3 py-2 font-medium">Nama</th>
                    <th class="px-3 py-2 font-medium">Phone</th>
                    <th class="px-3 py-2 font-medium">Email</th>
                    <th class="px-3 py-2 font-medium text-center">Assigned Leads</th>
                    <th class="px-3 py-2 font-medium">Status</th>
                    <th class="px-3 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="s in staff" :key="s.id">
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900" x-text="s.name"></td>
                        <td class="px-3 py-2 text-xs font-mono text-gray-600" x-text="s.phone || '—'"></td>
                        <td class="px-3 py-2 text-xs text-gray-600" x-text="s.email || '—'"></td>
                        <td class="px-3 py-2 text-center font-semibold" x-text="s.assigned_count"></td>
                        <td class="px-3 py-2">
                            <button @click="toggle(s)"
                                :class="s.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500'"
                                class="text-xs px-2.5 py-0.5 rounded-full font-medium"
                                x-text="s.is_active ? 'ACTIVE' : 'INACTIVE'"></button>
                        </td>
                        <td class="px-3 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="openEdit(s)" class="text-emerald-700 hover:underline text-xs">Edit</button>
                            <button @click="destroy(s)" class="text-rose-600 hover:underline text-xs">Padam</button>
                        </td>
                    </tr>
                </template>
                <tr x-show="!staff.length">
                    <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-400">
                        Belum ada staff. Klik <b>+ Tambah Staff</b> untuk mula.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="text-xs text-gray-500 bg-sky-50 border border-sky-200 rounded-lg p-3">
        <b>💡 Tips:</b> Untuk agih leads bergilir kepada staff — pergi ke <b>Leads</b> → klik <b>Bahagikan Leads</b> di kanan atas. Sistem akan agih semua lead yang belum ditugaskan mengikut round-robin.
    </div>

    {{-- Editor modal --}}
    <div x-show="editorOpen" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="editorOpen = false">
        <div class="bg-white rounded-2xl p-5 max-w-md w-full shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-900" x-text="draft.id ? 'Edit Staff' : 'Staff Baru'"></h3>
                <button @click="editorOpen = false" class="text-gray-400 text-xl leading-none">×</button>
            </div>
            <label class="block text-sm">
                <span class="text-gray-700">Nama</span>
                <input type="text" x-model="draft.name" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
            </label>
            <label class="block text-sm">
                <span class="text-gray-700">Phone (WhatsApp)</span>
                <input type="text" x-model="draft.phone" placeholder="011-2233 4455" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
            </label>
            <label class="block text-sm">
                <span class="text-gray-700">Email</span>
                <input type="email" x-model="draft.email" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" x-model="draft.is_active" class="rounded border-gray-300" />
                <span class="text-gray-700">Aktif (terima assignment)</span>
            </label>
            <div class="flex justify-end gap-2 pt-2">
                <button @click="editorOpen = false" class="text-sm text-gray-600 px-4 py-1.5">Batal</button>
                <button @click="save()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-1.5">Simpan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function staffPage() {
    return {
        staff: [], editorOpen: false, draft: {},
        async load() {
            const r = await fetch('/admin/whatsapp-agent/api/staff', {credentials: 'same-origin'});
            const d = await r.json();
            this.staff = d.staff || [];
        },
        openNew() {
            this.draft = {name: '', phone: '', email: '', is_active: true, weight: 1};
            this.editorOpen = true;
        },
        openEdit(s) { this.draft = {...s}; this.editorOpen = true; },
        async save() {
            if (!this.draft.name?.trim()) { alert('Nama wajib.'); return; }
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const url = this.draft.id
                ? `/admin/whatsapp-agent/api/staff/${this.draft.id}`
                : '/admin/whatsapp-agent/api/staff';
            const method = this.draft.id ? 'PATCH' : 'POST';
            const r = await fetch(url, {
                method, credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify(this.draft),
            });
            if (r.ok) { this.editorOpen = false; await this.load(); }
        },
        async toggle(s) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`/admin/whatsapp-agent/api/staff/${s.id}/toggle`, {
                method: 'POST', credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': csrfToken},
            });
            if (r.ok) s.is_active = !s.is_active;
        },
        async destroy(s) {
            if (!confirm(`Padam ${s.name}? Assignment mereka akan jadi tak assigned.`)) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`/admin/whatsapp-agent/api/staff/${s.id}`, {
                method: 'DELETE', credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': csrfToken},
            });
            if (r.ok) this.staff = this.staff.filter(x => x.id !== s.id);
        },
    };
}
</script>
@endpush
@endsection
