@extends('layouts.admin')
@section('title', 'Broadcast')
@section('content')
<div class="p-6 space-y-4" x-data="broadcastPage()" x-init="init()">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">📣 Broadcast</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            Mass send mesej kepada segmen pesakit. Hormati consent — bot hanya hantar ke pesakit yang <code>consent_marketing=yes</code> & tidak opted out.
        </p>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 flex gap-4">
        <template x-for="t in tabs" :key="t.key">
            <button @click="tab = t.key"
                :class="tab === t.key ? 'text-emerald-700 border-emerald-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
                class="text-sm font-medium pb-2 border-b-2 transition-colors" x-text="t.label"></button>
        </template>
    </div>

    {{-- Tab: Buat Campaign --}}
    <div x-show="tab === 'compose'" x-cloak class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- 1. Audience --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h3 class="font-semibold text-gray-900">1. Pilih Audience</h3>

            <div class="grid grid-cols-2 gap-2">
                <button @click="audienceMode = 'crm'"
                    :class="audienceMode === 'crm' ? 'border-emerald-500 bg-emerald-50 text-emerald-900' : 'border-gray-200 hover:border-gray-300'"
                    class="border rounded-lg p-3 text-left transition-colors">
                    <div class="text-sm font-medium">👥 Dari CRM</div>
                    <div class="text-[11px] text-gray-500">Filter pesakit sedia ada (consent_marketing=yes only).</div>
                </button>
                <button @click="audienceMode = 'csv'"
                    :class="audienceMode === 'csv' ? 'border-emerald-500 bg-emerald-50 text-emerald-900' : 'border-gray-200 hover:border-gray-300'"
                    class="border rounded-lg p-3 text-left transition-colors">
                    <div class="text-sm font-medium">📄 Upload CSV</div>
                    <div class="text-[11px] text-gray-500">Senarai cold lead (di luar CRM). Sesuai untuk Meta Template.</div>
                </button>
            </div>

            <template x-if="audienceMode === 'crm'">
                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-2">
                        <label class="text-xs">
                            <span class="text-gray-600">Tier</span>
                            <select x-model="filter.tier" @change="refreshAudience()" class="mt-1 w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                                <option value="">Semua</option>
                                <option value="hot">hot</option><option value="warm">warm</option>
                                <option value="new_lead">new_lead</option><option value="cold">cold</option>
                            </select>
                        </label>
                        <label class="text-xs">
                            <span class="text-gray-600">Stage</span>
                            <select x-model="filter.stage" @change="refreshAudience()" class="mt-1 w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                                <option value="">Semua</option>
                                <option value="new_lead">new_lead</option><option value="contacted">contacted</option>
                                <option value="qualified">qualified</option><option value="appointment_offered">appointment_offered</option>
                                <option value="appointment_booked">appointment_booked</option><option value="not_interested">not_interested</option>
                            </select>
                        </label>
                        <label class="text-xs">
                            <span class="text-gray-600">Servis</span>
                            <select x-model="filter.service" @change="refreshAudience()" class="mt-1 w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                                <option value="">Semua</option>
                                <option value="khatan">Khatan</option><option value="minor_surgery">Minor Surgery</option>
                                <option value="knee_pain">Lutut</option><option value="diabetes">Diabetes</option>
                            </select>
                        </label>
                    </div>

                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                        <div class="text-[10px] uppercase tracking-wide text-emerald-700 font-semibold">Audience (consent_marketing=yes only)</div>
                        <div class="text-3xl font-bold text-emerald-900 mt-0.5" x-text="audienceCount"></div>
                        <div class="text-xs text-emerald-800 mt-0.5">pesakit akan terima</div>
                        <div x-show="skippedByFreqCap > 0" class="text-[11px] text-amber-700 mt-1">
                            (<span x-text="skippedByFreqCap"></span> di-skip sebab dah terima 2 broadcasts dalam 7 hari)
                        </div>
                    </div>

                    <div x-show="audienceCount === 0" class="text-center text-sm text-gray-400 py-6">
                        Tiada audience matching filter. Pastikan pesakit ada consent_marketing=yes.
                    </div>
                </div>
            </template>

            <template x-if="audienceMode === 'csv'">
                <div class="space-y-2">
                    <label class="block text-sm">
                        <span class="text-gray-600">Upload CSV (phone di column pertama)</span>
                        <input type="file" accept=".csv,.txt" @change="handleCsv($event)" class="mt-1 w-full text-sm" />
                    </label>
                    <div x-show="csvPhones.length" class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                        <div class="text-[10px] uppercase tracking-wide text-emerald-700 font-semibold">CSV Audience</div>
                        <div class="text-3xl font-bold text-emerald-900 mt-0.5" x-text="csvPhones.length"></div>
                        <div class="text-xs text-emerald-800 mt-0.5">nombor akan terima</div>
                    </div>
                    <p class="text-[11px] text-gray-500">
                        CSV bypass consent check. Guna hanya kalau nombor tu dah opt-in luar CRM (cth: manual sign-up).
                    </p>
                </div>
            </template>
        </div>

        {{-- 2. Compose --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h3 class="font-semibold text-gray-900">2. Compose Mesej</h3>

            <div class="grid grid-cols-2 gap-2">
                <button @click="mode = 'freeform'"
                    :class="mode === 'freeform' ? 'border-emerald-500 bg-emerald-50 text-emerald-900' : 'border-gray-200 hover:border-gray-300'"
                    class="border rounded-lg p-3 text-left transition-colors">
                    <div class="text-sm font-medium">💬 Freeform</div>
                    <div class="text-[11px] text-gray-500">Cuma untuk pesakit yang dah message bot dalam 24 jam terakhir.</div>
                </button>
                <button @click="mode = 'meta_template'"
                    :class="mode === 'meta_template' ? 'border-emerald-500 bg-emerald-50 text-emerald-900' : 'border-gray-200 hover:border-gray-300'"
                    class="border rounded-lg p-3 text-left transition-colors">
                    <div class="text-sm font-medium">📋 Meta Template</div>
                    <div class="text-[11px] text-gray-500">Untuk cold outreach. Wajib pakai template yang dah APPROVED Meta.</div>
                </button>
            </div>

            <label class="block text-sm">
                <span class="text-gray-700">Nama Campaign</span>
                <input type="text" x-model="campaignName" placeholder="e.g. Reminder Diabetes Reset May 2026"
                    class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
            </label>

            <template x-if="mode === 'freeform'">
                <div>
                    <label class="block text-sm">
                        <span class="text-gray-700">Mesej (gunakan <code>{nama}</code> untuk personalize)</span>
                        <textarea x-model="messageBody" rows="7" placeholder="Hai {nama}! Klinik Bustari nak share..."
                            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono"></textarea>
                    </label>
                    <div class="flex items-center justify-between mt-1">
                        <div class="text-[11px] text-gray-500"><span x-text="(messageBody || '').length"></span> aksara</div>
                        <button @click="showTemplatePicker = true" class="text-[11px] text-emerald-700 hover:underline">📋 Pilih dari template</button>
                    </div>
                </div>
            </template>

            <template x-if="mode === 'meta_template'">
                <div class="text-sm text-gray-600 bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <b>Coming soon</b> — pull APPROVED templates dari Meta Cloud API. Buat masa ni sila guna Freeform mode.
                </div>
            </template>

            <label class="block text-sm">
                <span class="text-gray-700">Delay antara mesej (ms)</span>
                <input type="number" x-model.number="delayMs" min="500" max="60000" step="100"
                    class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                <span class="text-[11px] text-gray-500">Default 1500ms. Lebih tinggi = lebih selamat dari rate-limit WhatsApp.</span>
            </label>

            <button @click="startBroadcast()"
                :disabled="!canSend() || sending"
                :class="canSend() && !sending ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
                class="w-full text-white text-sm font-semibold rounded-lg py-2.5">
                <span x-show="!sending">Hantar ke <span x-text="totalToSend()"></span> pesakit</span>
                <span x-show="sending">Menghantar… <span x-text="progress.done"></span> / <span x-text="progress.total"></span></span>
            </button>

            <div class="text-[11px] text-gray-500 bg-amber-50 border border-amber-200 rounded-lg p-2.5">
                ⚠️ Broadcast hormati consent. Bot tidak akan hantar ke pesakit yang opt-out atau consent_marketing != "yes". Frequency cap: 2 broadcasts per 7 hari per pesakit.
            </div>
        </div>
    </div>

    {{-- Tab: Sejarah --}}
    <div x-show="tab === 'history'" x-cloak class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-3 py-2 font-medium">Tarikh</th>
                    <th class="px-3 py-2 font-medium">Nama</th>
                    <th class="px-3 py-2 font-medium">Mode</th>
                    <th class="px-3 py-2 font-medium text-center">Total</th>
                    <th class="px-3 py-2 font-medium text-center">Sent</th>
                    <th class="px-3 py-2 font-medium text-center">Failed</th>
                    <th class="px-3 py-2 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="h in history" :key="h.id">
                    <tr>
                        <td class="px-3 py-2 text-xs text-gray-600" x-text="h.started_at?.replace('T', ' ').slice(0, 16)"></td>
                        <td class="px-3 py-2 font-medium text-gray-900" x-text="h.name"></td>
                        <td class="px-3 py-2 text-xs text-gray-600" x-text="h.mode"></td>
                        <td class="px-3 py-2 text-center" x-text="h.total_count"></td>
                        <td class="px-3 py-2 text-center text-emerald-700 font-medium" x-text="h.sent_count"></td>
                        <td class="px-3 py-2 text-center text-rose-600" x-text="h.failed_count"></td>
                        <td class="px-3 py-2">
                            <span :class="statusColor(h.status)" class="text-[10px] uppercase font-semibold px-2 py-0.5 rounded" x-text="h.status"></span>
                        </td>
                    </tr>
                </template>
                <tr x-show="!history.length">
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-400">Tiada broadcast lagi.</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Tab: Templates (lokal) --}}
    <div x-show="tab === 'templates'" x-cloak class="space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Saved messages yang boleh guna semula untuk broadcast atau reply.</p>
            <button @click="openNewTemplate()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-1.5">+ Template Baru</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template x-for="t in templates" :key="t.id">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="font-semibold text-gray-900" x-text="t.name"></div>
                            <div class="text-[10px] uppercase tracking-wide text-gray-400 mt-0.5" x-text="t.category || 'general'"></div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="useTemplate(t)" class="text-xs text-emerald-700 hover:underline">Guna</button>
                            <button @click="editTemplate(t)" class="text-xs text-gray-600 hover:underline">Edit</button>
                            <button @click="destroyTemplate(t)" class="text-xs text-rose-600 hover:underline">Padam</button>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 mt-2 whitespace-pre-line" x-text="(t.body || '').slice(0, 200) + ((t.body || '').length > 200 ? '…' : '')"></div>
                </div>
            </template>
            <div x-show="!templates.length" class="col-span-full text-center text-sm text-gray-400 py-8">
                Tiada template. Klik <b>+ Template Baru</b> untuk mula.
            </div>
        </div>
    </div>

    {{-- Tab: Meta Templates --}}
    <div x-show="tab === 'meta'" x-cloak class="bg-white rounded-2xl border border-gray-100 p-8 text-center">
        <div class="text-4xl mb-3">📋</div>
        <div class="text-sm text-gray-600">Meta Cloud API template listing — <b>coming soon</b>.</div>
        <div class="text-xs text-gray-400 mt-1">Akan pull APPROVED templates + membenarkan cold outreach.</div>
    </div>

    {{-- Template picker modal --}}
    <div x-show="showTemplatePicker" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="showTemplatePicker = false">
        <div class="bg-white rounded-2xl p-5 max-w-lg w-full shadow-xl max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900">Pilih Template</h3>
                <button @click="showTemplatePicker = false" class="text-gray-400 text-xl leading-none">×</button>
            </div>
            <div class="space-y-2">
                <template x-for="t in templates" :key="t.id">
                    <button @click="useTemplate(t); showTemplatePicker = false" class="block w-full text-left border border-gray-200 hover:bg-gray-50 rounded-lg p-3">
                        <div class="font-medium text-gray-900 text-sm" x-text="t.name"></div>
                        <div class="text-xs text-gray-500 mt-1 line-clamp-2" x-text="t.body"></div>
                    </button>
                </template>
                <div x-show="!templates.length" class="text-sm text-gray-400 text-center py-4">Tiada template.</div>
            </div>
        </div>
    </div>

    {{-- Template editor modal --}}
    <div x-show="templateEditorOpen" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="templateEditorOpen = false">
        <div class="bg-white rounded-2xl p-5 max-w-lg w-full shadow-xl">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900" x-text="templateDraft.id ? 'Edit Template' : 'Template Baru'"></h3>
                <button @click="templateEditorOpen = false" class="text-gray-400 text-xl leading-none">×</button>
            </div>
            <div class="space-y-3">
                <label class="block text-sm">
                    <span class="text-gray-700">Nama</span>
                    <input type="text" x-model="templateDraft.name" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
                </label>
                <label class="block text-sm">
                    <span class="text-gray-700">Kategori</span>
                    <select x-model="templateDraft.category" class="mt-1 w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                        <option value="general">general</option>
                        <option value="reminder">reminder</option>
                        <option value="promo">promo</option>
                        <option value="followup">followup</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="text-gray-700">Mesej (gunakan <code>{nama}</code>)</span>
                    <textarea x-model="templateDraft.body" rows="6" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono"></textarea>
                </label>
                <div class="flex justify-end gap-2">
                    <button @click="templateEditorOpen = false" class="text-sm text-gray-600 hover:text-gray-900 px-4 py-1.5">Batal</button>
                    <button @click="saveTemplate()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-1.5">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Send progress overlay --}}
    <div x-show="sending" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
            <h3 class="font-semibold text-gray-900">📣 Broadcast sedang dijalankan</h3>
            <p class="text-sm text-gray-600 mt-1">
                <span x-text="progress.done"></span> / <span x-text="progress.total"></span> selesai
                <span class="text-gray-400">(<span x-text="Math.round((progress.done/Math.max(1,progress.total))*100)"></span>%)</span>
            </p>
            <div class="mt-3 bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="bg-emerald-500 h-full transition-all" :style="`width: ${(progress.done/Math.max(1,progress.total)*100)||0}%`"></div>
            </div>
            <div class="mt-3 text-xs text-gray-500 space-y-1">
                <div>✓ Sent: <b x-text="progress.sent"></b></div>
                <div>✗ Failed: <b x-text="progress.failed"></b></div>
                <div x-show="progress.currentPhone">Menghantar: <span class="font-mono" x-text="progress.currentPhone"></span></div>
            </div>
            <button @click="cancelBroadcast()" class="mt-4 text-sm text-rose-600 hover:underline">Batalkan</button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function broadcastPage() {
    return {
        tab: 'compose',
        tabs: [
            {key: 'compose',   label: 'Buat Campaign'},
            {key: 'history',   label: 'Sejarah'},
            {key: 'templates', label: 'Templates (lokal)'},
            {key: 'meta',      label: 'Meta Templates'},
        ],

        // Audience
        audienceMode: 'crm',
        filter: {tier: '', stage: '', service: ''},
        audienceCount: 0,
        skippedByFreqCap: 0,
        audiencePhones: [],
        csvPhones: [],

        // Compose
        mode: 'freeform',
        campaignName: '',
        messageBody: '',
        delayMs: 1500,

        // Sending
        sending: false,
        cancelFlag: false,
        broadcastId: null,
        progress: {done: 0, total: 0, sent: 0, failed: 0, currentPhone: ''},

        // History
        history: [],

        // Templates
        templates: [],
        showTemplatePicker: false,
        templateEditorOpen: false,
        templateDraft: {name: '', category: 'general', body: ''},

        async init() {
            await this.refreshAudience();
            await this.loadHistory();
            await this.loadTemplates();
        },
        async refreshAudience() {
            if (this.audienceMode !== 'crm') return;
            const url = new URL('/admin/whatsapp-agent/api/broadcasts/audience', window.location.origin);
            if (this.filter.tier) url.searchParams.set('tier', this.filter.tier);
            if (this.filter.stage) url.searchParams.set('stage', this.filter.stage);
            if (this.filter.service) url.searchParams.set('service', this.filter.service);
            const r = await fetch(url, {credentials: 'same-origin'});
            const d = await r.json();
            this.audiencePhones = d.phones || [];
            this.audienceCount = d.total || 0;
            this.skippedByFreqCap = d.skipped_by_freq_cap || 0;
        },
        handleCsv(ev) {
            const file = ev.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                const text = reader.result || '';
                const phones = String(text).split(/\r?\n/)
                    .map(l => l.split(',')[0].trim())
                    .filter(p => /^\+?\d[\d\s\-]{7,}$/.test(p))
                    .map(p => p.replace(/[\s\-]/g, ''));
                this.csvPhones = [...new Set(phones)];
            };
            reader.readAsText(file);
        },
        totalToSend() {
            return this.audienceMode === 'crm' ? this.audienceCount : this.csvPhones.length;
        },
        canSend() {
            if (!this.campaignName.trim()) return false;
            if (this.mode === 'freeform' && !this.messageBody.trim()) return false;
            if (this.mode === 'meta_template') return false;
            return this.totalToSend() > 0;
        },
        async startBroadcast() {
            if (!this.canSend()) return;
            const phones = this.audienceMode === 'crm' ? this.audiencePhones : this.csvPhones;
            if (!phones.length) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const cr = await fetch('/admin/whatsapp-agent/api/broadcasts', {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({
                    name: this.campaignName,
                    mode: this.mode,
                    audience_filter: this.audienceMode === 'crm' ? this.filter : {source: 'csv'},
                    message_body: this.messageBody,
                    delay_ms: this.delayMs,
                    phones: phones,
                }),
            });
            if (!cr.ok) { alert('Gagal create broadcast.'); return; }
            const {broadcast, phones: serverPhones} = await cr.json();
            this.broadcastId = broadcast.id;
            const targets = serverPhones && serverPhones.length ? serverPhones : phones;

            this.sending = true;
            this.cancelFlag = false;
            this.progress = {done: 0, total: targets.length, sent: 0, failed: 0, currentPhone: ''};

            for (const phone of targets) {
                if (this.cancelFlag) break;
                this.progress.currentPhone = phone;
                try {
                    const rr = await fetch(`/admin/whatsapp-agent/api/broadcasts/${this.broadcastId}/send-one`, {
                        method: 'POST', credentials: 'same-origin',
                        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                        body: JSON.stringify({phone}),
                    });
                    const rj = await rr.json();
                    if (rj.ok) this.progress.sent++;
                    else this.progress.failed++;
                } catch {
                    this.progress.failed++;
                }
                this.progress.done++;
                if (this.delayMs > 0) await new Promise(r => setTimeout(r, this.delayMs));
            }

            // Finalize
            await fetch(`/admin/whatsapp-agent/api/broadcasts/${this.broadcastId}/finalize`, {
                method: 'POST', credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': csrfToken},
            });

            this.progress.currentPhone = '';
            this.sending = false;
            await this.loadHistory();
            alert(`Broadcast siap.\n✓ Sent: ${this.progress.sent}\n✗ Failed: ${this.progress.failed}`);
        },
        async cancelBroadcast() {
            this.cancelFlag = true;
            if (this.broadcastId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                await fetch(`/admin/whatsapp-agent/api/broadcasts/${this.broadcastId}/cancel`, {
                    method: 'POST', credentials: 'same-origin',
                    headers: {'X-CSRF-TOKEN': csrfToken},
                });
            }
        },
        async loadHistory() {
            const r = await fetch('/admin/whatsapp-agent/api/broadcasts/history', {credentials: 'same-origin'});
            const d = await r.json();
            this.history = d.broadcasts || [];
        },
        statusColor(s) {
            return s === 'done' ? 'bg-emerald-100 text-emerald-700' :
                   s === 'running' ? 'bg-sky-100 text-sky-700' :
                   s === 'cancelled' ? 'bg-gray-100 text-gray-500' :
                   s === 'failed' ? 'bg-rose-100 text-rose-700' :
                   'bg-amber-100 text-amber-700';
        },

        // Templates
        async loadTemplates() {
            const r = await fetch('/admin/whatsapp-agent/api/templates', {credentials: 'same-origin'});
            const d = await r.json();
            this.templates = d.templates || [];
        },
        openNewTemplate() {
            this.templateDraft = {name: '', category: 'general', body: ''};
            this.templateEditorOpen = true;
        },
        editTemplate(t) {
            this.templateDraft = {...t};
            this.templateEditorOpen = true;
        },
        useTemplate(t) {
            this.messageBody = t.body || '';
            this.tab = 'compose';
            this.mode = 'freeform';
        },
        async saveTemplate() {
            if (!this.templateDraft.name?.trim() || !this.templateDraft.body?.trim()) {
                alert('Nama & body wajib.'); return;
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const url = this.templateDraft.id
                ? `/admin/whatsapp-agent/api/templates/${this.templateDraft.id}`
                : '/admin/whatsapp-agent/api/templates';
            const method = this.templateDraft.id ? 'PATCH' : 'POST';
            const r = await fetch(url, {
                method, credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify(this.templateDraft),
            });
            if (r.ok) {
                this.templateEditorOpen = false;
                await this.loadTemplates();
            }
        },
        async destroyTemplate(t) {
            if (!confirm(`Padam template "${t.name}"?`)) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch(`/admin/whatsapp-agent/api/templates/${t.id}`, {
                method: 'DELETE', credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': csrfToken},
            });
            if (r.ok) this.templates = this.templates.filter(x => x.id !== t.id);
        },
    };
}
</script>
@endpush
@endsection
