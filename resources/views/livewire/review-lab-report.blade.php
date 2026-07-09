<div style="max-width:820px;margin:2rem auto;font-family:system-ui;">
    <h1 style="font-size:1.3rem;">Review extracted values</h1>
    <p style="color:#6a7079;">
        Patient: <strong>{{ $report->patient->name }}</strong>
        &middot; Lab No: {{ $report->lab_no }} &middot; Status: DRAFT
    </p>
    <p style="background:#fff9ec;border:1px solid #e8c675;padding:.6rem .8rem;border-radius:6px;font-size:.85rem;">
        Semak setiap nilai terhadap PDF asal sebelum menjana laporan. Betulkan mana-mana
        yang tersilap baca. / Verify each value against the source PDF before generating
        the report. Correct any mis-reads.
    </p>

    <form wire:submit="finalise">
        <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
            <thead><tr style="background:#1f6f5c;color:#fff;">
                <th style="text-align:left;padding:5px 8px;">Marker</th>
                <th style="text-align:left;padding:5px 8px;">Value</th>
                <th style="text-align:left;padding:5px 8px;">Unit</th>
            </tr></thead>
            <tbody>
            @foreach($catalogue as $key => $def)
                <tr style="border-bottom:1px solid #e3ebe8;">
                    <td style="padding:4px 8px;">{{ $def['label'] }}</td>
                    <td style="padding:4px 8px;">
                        <input type="text" wire:model="values.{{ $key }}"
                               style="width:120px;padding:3px 6px;border:1px solid #cfe3db;border-radius:4px;">
                        @error('values.'.$key) <span style="color:#b23a48;">✗</span> @enderror
                    </td>
                    <td style="padding:4px 8px;color:#6a7079;">{{ $def['unit'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <label style="display:flex;gap:.5rem;margin:1rem 0;align-items:flex-start;">
            <input type="checkbox" wire:model="verified">
            <span>Saya sahkan nilai di atas telah disemak terhadap PDF makmal asal. /
            I confirm the values above have been verified against the source lab PDF.</span>
        </label>
        @error('verified') <div style="color:#b23a48;">{{ $message }}</div> @enderror

        <button type="submit" wire:loading.attr="disabled"
            style="background:#1f6f5c;color:#fff;padding:.6rem 1rem;border:0;border-radius:6px;">
            <span wire:loading.remove>Generate report</span>
            <span wire:loading>Scoring…</span>
        </button>
    </form>
</div>
