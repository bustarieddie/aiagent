<div style="max-width:640px;margin:2rem auto;font-family:system-ui;">
    <h1 style="font-size:1.3rem;">Upload Gnosis Lab Report (PDF)</h1>

    <form wire:submit="save" style="display:flex;flex-direction:column;gap:1rem;">
        @csrf {{-- CSRF on by default; shown for clarity --}}

        <input type="file" wire:model="pdf" accept="application/pdf">
        @error('pdf') <span style="color:#b23a48;">{{ $message }}</span> @enderror

        {{-- PDPA s.6: explicit, affirmative, UNTICKED consent for health-data processing --}}
        <label style="display:flex;gap:.5rem;align-items:flex-start;">
            <input type="checkbox" wire:model="consent">
            <span>Saya sahkan pesakit telah memberi persetujuan untuk pemprosesan data
            kesihatan ini / I confirm the patient has consented to processing this health data.
            <a href="{{ route('privacy') }}" target="_blank">Notis Privasi / Privacy Notice</a></span>
        </label>
        @error('consent') <span style="color:#b23a48;">{{ $message }}</span> @enderror

        <button type="submit" wire:loading.attr="disabled"
            style="background:#1f6f5c;color:#fff;padding:.6rem 1rem;border:0;border-radius:6px;">
            <span wire:loading.remove>Generate Report</span>
            <span wire:loading>Processing…</span>
        </button>
    </form>

    <p style="font-size:.8rem;color:#6a7079;margin-top:1rem;">
        Clinical decision-support only — not a diagnosis. Reviewed by a clinician.
    </p>
</div>
