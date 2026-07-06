@extends('layouts.admin')
@section('title', 'Patient Detail')
@section('content')
<div class="p-6" x-data="patientDetail('{{ $phone }}')" x-init="load()">
    <a href="{{ route('admin.patients') }}" class="text-emerald-700 text-xs hover:underline">← Kembali ke senarai</a>
    <template x-if="patient">
        <div class="mt-4 space-y-4">
            <h2 class="text-xl font-semibold text-gray-900" x-text="patient.name || 'Pesakit'"></h2>
            <div class="text-sm text-gray-500 font-mono" x-text="patient.phone"></div>
            <pre class="bg-gray-900 text-emerald-200 text-xs rounded-lg p-4 overflow-auto" x-text="JSON.stringify(patient, null, 2)"></pre>
        </div>
    </template>
    <template x-if="!patient">
        <div class="mt-4 text-sm text-gray-400">Memuat…</div>
    </template>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function patientDetail(phone) {
    return {
        patient: null,
        async load() {
            const r = await fetch(`/admin/whatsapp-agent/api/patients/${encodeURIComponent(phone)}`, {credentials: 'same-origin'});
            if (r.ok) this.patient = await r.json();
        },
    };
}
</script>
@endpush
@endsection
