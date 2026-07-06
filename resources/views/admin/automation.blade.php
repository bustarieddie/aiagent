@extends('layouts.admin')
@section('title', 'Automation')
@section('content')
<div class="p-6 space-y-4" x-data="automationPage()" x-init="load()">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">🤖 WhatsApp Automation</h2>
            <p class="text-sm text-gray-500">Rules yang auto-run bila trigger matched. Bot akan check setiap incoming message.</p>
        </div>
        <button @click="openNew()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-1.5">
            + Tambah Rule
        </button>
    </div>

    {{-- Rules list --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-3 py-2 font-medium">Status</th>
                    <th class="px-3 py-2 font-medium">Nama</th>
                    <th class="px-3 py-2 font-medium">Trigger</th>
                    <th class="px-3 py-2 font-medium">Action</th>
                    <th class="px-3 py-2 font-medium text-center">Fired</th>
                    <th class="px-3 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="r in rules" :key="r.id">
                    <tr>
                        <td class="px-3 py-2">
                            <button @click="toggle(r)"
                                :class="r.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500'"
                                class="text-xs px-2.5 py-0.5 rounded-full font-medium">
                                <span x-text="r.is_active ? 'ON' : 'OFF'"></span>
                            </button>
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-900" x-text="r.name"></td>
                        <td class="px-3 py-2 text-xs text-gray-700">
                            <div class="uppercase tracking-wide text-[10px] text-gray-400" x-text="r.trigger_type"></div>
                            <div x-text="describeTrigger(r)"></div>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-700">
                            <div class="uppercase tracking-wide text-[10px] text-gray-400" x-text="r.action_type"></div>
                            <div class="max-w-md truncate" x-text="describeAction(r)"></div>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600 text-center" x-text="r.fire_count || 0"></td>
                        <td class="px-3 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="openEdit(r)" class="text-emerald-700 hover:underline text-xs">Edit</button>
                            <button @click="destroy(r)" class="text-rose-600 hover:underline text-xs">Padam</button>
                        </td>
                    </tr>
                </template>
                <tr x-show="!rules.length">
                    <td colspan="6" class="px-3 py-8 text-center text-gray-400 text-sm">
                        Tiada rule lagi. Klik <b>+ Tambah Rule</b> untuk mula.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="text-xs text-gray-500 bg-amber-50 border border-amber-200 rounded-lg p-3">
        <b>Nota:</b> Rules disimpan di Laravel DB. Bot Python akan check rules bila message masuk (integration
        pending — buat masa ni rules tak fire lagi sampai bot side wired up). Boleh guna page ni untuk plan &
        draft rules dulu.
    </div>

    {{-- Rule editor modal --}}
    <div x-show="editorOpen" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="editorOpen = false">
        <div class="bg-white rounded-2xl p-6 max-w-lg w-full shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900" x-text="draft.id ? 'Edit Rule' : 'Rule Baru'"></h3>
                <button @click="editorOpen = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>

            <label class="block text-sm">
                <span class="text-gray-700">Nama rule</span>
                <input type="text" x-model="draft.name" placeholder="Cth: Follow-up 24 jam" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5" />
            </label>

            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm">
                    <span class="text-gray-700">Trigger</span>
                    <select x-model="draft.trigger_type" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                        <option value="keyword_in">Keyword dalam mesej</option>
                        <option value="no_reply_hours">Tiada balas selepas N jam</option>
                        <option value="new_lead">Lead baru</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="text-gray-700">Action</span>
                    <select x-model="draft.action_type" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                        <option value="send_message">Hantar mesej</option>
                        <option value="set_stage">Set stage</option>
                        <option value="set_tier">Set tier</option>
                        <option value="add_tag">Add tag</option>
                        <option value="takeover">Human takeover</option>
                    </select>
                </label>
            </div>

            {{-- Trigger config --}}
            <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                <div class="text-xs font-medium text-gray-600 uppercase tracking-wide">Trigger Config</div>
                <template x-if="draft.trigger_type === 'keyword_in'">
                    <label class="block text-sm">
                        <span class="text-gray-700">Keywords (pisah dengan koma)</span>
                        <input type="text" x-model="draft._keywords" placeholder="harga, murah, kos" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5" />
                        <p class="text-[11px] text-gray-500 mt-1">Case-insensitive. Rule fire bila mana-mana keyword muncul.</p>
                    </label>
                </template>
                <template x-if="draft.trigger_type === 'no_reply_hours'">
                    <label class="block text-sm">
                        <span class="text-gray-700">Hantar selepas berapa jam</span>
                        <input type="number" x-model.number="draft._hours" min="1" max="168" class="mt-1 w-24 text-sm border border-gray-300 rounded-lg px-3 py-1.5" />
                    </label>
                </template>
                <template x-if="draft.trigger_type === 'new_lead'">
                    <p class="text-xs text-gray-500">Fire sekali bila conversation baru masuk (first message).</p>
                </template>
            </div>

            {{-- Action config --}}
            <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                <div class="text-xs font-medium text-gray-600 uppercase tracking-wide">Action Config</div>
                <template x-if="draft.action_type === 'send_message'">
                    <label class="block text-sm">
                        <span class="text-gray-700">Mesej</span>
                        <textarea x-model="draft._message" rows="4" placeholder="Hi {nama}, terima kasih hubungi Klinik Bustari…" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2"></textarea>
                        <p class="text-[11px] text-gray-500 mt-1">Placeholder: <code>{nama}</code> = nama pesakit.</p>
                    </label>
                </template>
                <template x-if="draft.action_type === 'set_stage'">
                    <label class="block text-sm">
                        <span class="text-gray-700">Stage baru</span>
                        <select x-model="draft._stage" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                            <option value="new_lead">new_lead</option>
                            <option value="contacted">contacted</option>
                            <option value="qualified">qualified</option>
                            <option value="appointment_offered">appointment_offered</option>
                            <option value="appointment_booked">appointment_booked</option>
                            <option value="not_interested">not_interested</option>
                        </select>
                    </label>
                </template>
                <template x-if="draft.action_type === 'set_tier'">
                    <label class="block text-sm">
                        <span class="text-gray-700">Tier baru</span>
                        <select x-model="draft._tier" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                            <option value="hot">hot</option>
                            <option value="warm">warm</option>
                            <option value="new_lead">new_lead</option>
                            <option value="cold">cold</option>
                        </select>
                    </label>
                </template>
                <template x-if="draft.action_type === 'add_tag'">
                    <label class="block text-sm">
                        <span class="text-gray-700">Tag</span>
                        <input type="text" x-model="draft._tag" placeholder="cth: hot_lead" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5" />
                    </label>
                </template>
                <template x-if="draft.action_type === 'takeover'">
                    <p class="text-xs text-gray-500">Flag conversation sebagai human takeover (bot berhenti auto-reply).</p>
                </template>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" x-model="draft.is_active" class="rounded border-gray-300" />
                <span class="text-gray-700">Rule aktif</span>
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <button @click="editorOpen = false" class="text-sm text-gray-600 hover:text-gray-900 px-4 py-1.5">Batal</button>
                <button @click="save()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-1.5">Simpan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function automationPage() {
    return {
        rules: [], editorOpen: false, draft: {},
        async load() {
            const r = await fetch('/admin/whatsapp-agent/api/automation', {credentials: 'same-origin'});
            const d = await r.json();
            this.rules = d.rules || [];
        },
        openNew() {
            this.draft = this.blankDraft();
            this.editorOpen = true;
        },
        openEdit(r) {
            this.draft = {
                id: r.id, name: r.name, is_active: r.is_active,
                trigger_type: r.trigger_type, action_type: r.action_type,
                _keywords: (r.trigger_config?.keywords || []).join(', '),
                _hours: r.trigger_config?.hours || 24,
                _message: r.action_config?.message || '',
                _stage: r.action_config?.stage || 'contacted',
                _tier: r.action_config?.tier || 'warm',
                _tag: r.action_config?.tag || '',
            };
            this.editorOpen = true;
        },
        blankDraft() {
            return {
                name: '', is_active: true,
                trigger_type: 'keyword_in', action_type: 'send_message',
                _keywords: '', _hours: 24, _message: '',
                _stage: 'contacted', _tier: 'warm', _tag: '',
            };
        },
        async save() {
            if (!this.draft.name?.trim()) { alert('Nama rule wajib.'); return; }
            const payload = {
                name: this.draft.name.trim(),
                trigger_type: this.draft.trigger_type,
                action_type: this.draft.action_type,
                is_active: !!this.draft.is_active,
                trigger_config: this.buildTriggerConfig(),
                action_config: this.buildActionConfig(),
            };
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const url = this.draft.id
                ? `/admin/whatsapp-agent/api/automation/${this.draft.id}`
                : '/admin/whatsapp-agent/api/automation';
            const method = this.draft.id ? 'PATCH' : 'POST';
            const r = await fetch(url, {
                method,
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify(payload),
            });
            if (r.ok) {
                this.editorOpen = false;
                await this.load();
            } else {
                const err = await r.json().catch(() => ({}));
                alert('Gagal: ' + (err.message || r.statusText));
            }
        },
        buildTriggerConfig() {
            if (this.draft.trigger_type === 'keyword_in') {
                const kws = (this.draft._keywords || '').split(',').map(s => s.trim()).filter(Boolean);
                return {keywords: kws};
            }
            if (this.draft.trigger_type === 'no_reply_hours') {
                return {hours: Number(this.draft._hours) || 24};
            }
            return {};
        },
        buildActionConfig() {
            switch (this.draft.action_type) {
                case 'send_message': return {message: this.draft._message || ''};
                case 'set_stage': return {stage: this.draft._stage};
                case 'set_tier': return {tier: this.draft._tier};
                case 'add_tag': return {tag: this.draft._tag || ''};
                default: return {};
            }
        },
        async toggle(r) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const resp = await fetch(`/admin/whatsapp-agent/api/automation/${r.id}/toggle`, {
                method: 'POST', credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': csrfToken},
            });
            if (resp.ok) r.is_active = !r.is_active;
        },
        async destroy(r) {
            if (!confirm(`Padam rule "${r.name}"?`)) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const resp = await fetch(`/admin/whatsapp-agent/api/automation/${r.id}`, {
                method: 'DELETE', credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': csrfToken},
            });
            if (resp.ok) this.rules = this.rules.filter(x => x.id !== r.id);
        },
        describeTrigger(r) {
            const cfg = r.trigger_config || {};
            switch (r.trigger_type) {
                case 'keyword_in': return 'Match: ' + (cfg.keywords || []).join(', ');
                case 'no_reply_hours': return `Selepas ${cfg.hours || '?'} jam tiada balas`;
                case 'new_lead': return 'Bila conversation baru';
                default: return '—';
            }
        },
        describeAction(r) {
            const cfg = r.action_config || {};
            switch (r.action_type) {
                case 'send_message': return cfg.message || '(kosong)';
                case 'set_stage': return 'Set stage → ' + (cfg.stage || '?');
                case 'set_tier': return 'Set tier → ' + (cfg.tier || '?');
                case 'add_tag': return 'Add tag: ' + (cfg.tag || '?');
                case 'takeover': return 'Flag human takeover';
                default: return '—';
            }
        },
    };
}
</script>
@endpush
@endsection
