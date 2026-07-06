@extends('layouts.admin')
@section('title', 'Automation')
@section('content')
<div class="p-6 space-y-4 max-w-5xl" x-data="automationPage()" x-init="load()">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">🤖 WhatsApp Automation</h2>
            <p class="text-sm text-gray-500">System automations yang run ikut schedule. Toggle ON/OFF ikut keperluan klinik.</p>
        </div>
    </div>

    <div class="space-y-3">
        <template x-for="r in rules" :key="r.id">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-start gap-4">
                {{-- Icon --}}
                <div class="w-11 h-11 rounded-xl bg-gray-50 flex items-center justify-center text-2xl shrink-0" x-text="r.icon || '⚙️'"></div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-semibold text-gray-900" x-text="r.name"></h3>
                        <span :class="r.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                              class="text-[10px] uppercase tracking-wide font-semibold px-2 py-0.5 rounded"
                              x-text="r.is_active ? 'ENABLED' : 'DISABLED'"></span>
                    </div>
                    <p class="text-sm text-gray-600 mt-0.5" x-text="r.description"></p>
                    <div class="text-xs text-gray-500 mt-1.5 flex items-center gap-1">
                        <span>📅</span>
                        <span x-text="r.schedule_label"></span>
                        <span x-show="r.schedule_cron" class="text-gray-400 font-mono">(<span x-text="r.schedule_cron"></span>)</span>
                    </div>
                    <div class="text-[11px] text-gray-500 mt-2 flex items-center gap-3">
                        <span>Last run: <span :class="r.last_fired_at ? 'text-gray-700' : 'text-gray-400'" x-text="r.last_fired_at || 'tiada'"></span></span>
                        <span class="text-gray-300">·</span>
                        <span>7d: <b class="text-gray-700" x-text="r.runs_last_7d || 0"></b> runs · <b class="text-gray-700" x-text="r.sent_last_7d || 0"></b> sent</span>
                    </div>
                </div>

                {{-- Right controls --}}
                <div class="flex flex-col items-end gap-2 shrink-0">
                    <button @click="toggle(r)"
                        :class="r.is_active ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-gray-200 text-gray-600 hover:bg-gray-300'"
                        class="text-xs font-semibold uppercase tracking-wide rounded-full px-4 py-1.5 transition-colors"
                        x-text="r.is_active ? 'ON' : 'OFF'"></button>
                    <a href="#" @click.prevent="showLog(r)" class="text-[11px] text-emerald-700 hover:underline">Log</a>
                </div>
            </div>
        </template>

        <div x-show="!rules.length" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-sm text-gray-400">
            Belum ada system automations. Run <code class="bg-gray-100 px-1 py-0.5 rounded">php artisan automation:seed</code> di server.
        </div>
    </div>

    <div class="text-xs text-gray-500 bg-amber-50 border border-amber-200 rounded-lg p-3">
        <b>Nota:</b> Rules disimpan di Laravel DB. Cron runners untuk Appointment Reminder / Follow-up / Reactivation belum wired ke Python bot — sedang dibina. Boleh enable dari sekarang untuk plan schedule.
    </div>

    {{-- Log modal --}}
    <div x-show="logOpen" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="logOpen = false">
        <div class="bg-white rounded-2xl p-6 max-w-lg w-full shadow-xl">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-900" x-text="logRule?.name || 'Log'"></h3>
                <button @click="logOpen = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <div class="text-sm text-gray-700 space-y-2">
                <div>Schedule: <span class="font-mono text-xs" x-text="logRule?.schedule_label + ' (' + (logRule?.schedule_cron || 'on-demand') + ')'"></span></div>
                <div>Last run: <span x-text="logRule?.last_fired_at || 'tiada'"></span></div>
                <div>7-day: <b x-text="logRule?.runs_last_7d || 0"></b> runs, <b x-text="logRule?.sent_last_7d || 0"></b> sent</div>
                <div>Lifetime fires: <b x-text="logRule?.fire_count || 0"></b></div>
            </div>
            <p class="text-xs text-gray-400 mt-4">Log detail akan tunjuk bila runner wired ke bot.</p>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function automationPage() {
    return {
        rules: [], logOpen: false, logRule: null,
        async load() {
            const r = await fetch('/admin/whatsapp-agent/api/automation', {credentials: 'same-origin'});
            const d = await r.json();
            this.rules = d.rules || [];
        },
        async toggle(r) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const resp = await fetch(`/admin/whatsapp-agent/api/automation/${r.id}/toggle`, {
                method: 'POST', credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': csrfToken},
            });
            if (resp.ok) r.is_active = !r.is_active;
        },
        showLog(r) {
            this.logRule = r;
            this.logOpen = true;
        },
    };
}
</script>
@endpush
@endsection
