<!DOCTYPE html><html><head><meta charset="utf-8">@include('reports._style')</head><body>
<div class="cover">
  <div style="letter-spacing:2px;color:#b23a48;font-size:8px;font-weight:bold;">FUNCTIONAL MEDICINE · CONFIDENTIAL CLINICAL DOCUMENT</div>
  <h1>Functional Medicine Clinical Interpretation Report</h1>
  <div class="sub">Practitioner Version — Confidential Clinical Document</div>
</div>

<table class="meta">
  <tr><th>Patient</th><td>{{ $patient->name }}</td><th>Sex / Age</th><td>{{ $patient->sex }} / {{ $patient->age }}</td></tr>
  <tr><th>Lab No.</th><td>{{ $report->lab_no }}</td><th>Engine</th><td>v{{ $interp->engine_version ?? '—' }}</td></tr>
</table>

<div class="disc"><strong>DISCLAIMER:</strong> {{ $disclaimer }} Functional ranges are narrower than lab reference ranges and are decision-support, not diagnostic thresholds.</div>

@if($interp && count($interp->critical ?? []))
<h2>⚠ Critical Findings — Ranked</h2>
<table class="crit"><tr><th>#</th><th>Biomarker</th><th>Result</th><th>Status</th></tr>
@foreach($interp->critical as $i => $c)
  <tr><td>{{ $i+1 }}</td><td>{{ $c['label'] }}</td><td>{{ $c['value'] }} {{ $c['unit'] }}</td>
      <td><span class="pill {{ $c['status'] }}">{{ $c['status'] }}</span></td></tr>
@endforeach
</table>
@endif

<h2>Full Functional Interpretation by System</h2>
@php $bySystem = $results->groupBy(fn($r) => \App\Support\FunctionalRanges::definition($r->marker_key)['system'] ?? 'Other'); @endphp
@foreach($bySystem as $system => $rows)
  <h3 style="margin:8px 0 2px;color:#14332b;font-size:10px;">{{ $system }}</h3>
  <table><tr><th>Biomarker</th><th>Result</th><th>Functional Range</th><th>Status</th></tr>
  @foreach($rows as $r)
    @php $b = \App\Support\FunctionalRanges::band($r->marker_key, $patient->sex ?? 'M');
         $rng = $b ? (($b[0]??'') . '–' . ($b[1]??'')) : '—'; @endphp
    <tr><td>{{ \App\Support\FunctionalRanges::definition($r->marker_key)['label'] ?? $r->marker_key }}</td>
        <td>{{ $r->value }} {{ $r->unit }}</td><td>{{ $rng }}</td>
        <td><span class="pill {{ $r->status }}">{{ $r->status }}</span></td></tr>
  @endforeach
  </table>
@endforeach

@if($interp && count($interp->ratios ?? []))
<h2>Derived Ratios</h2>
<table><tr><th>Ratio</th><th>Value</th><th>Target</th><th>Status</th></tr>
@foreach($interp->ratios as $r)
  <tr><td>{{ $r['label'] }}</td><td>{{ $r['value'] }}</td><td>{{ $r['target'] }}</td>
      <td><span class="pill {{ $r['status'] }}">{{ $r['status'] }}</span></td></tr>
@endforeach
</table>
@endif


@if($interp && count($interp->narrative['nodes'] ?? []))
<h2>Systemic Dysregulation Nodes (auto-derived)</h2>
<table><tr><th>Node</th><th>Priority</th><th>Rationale</th></tr>
@foreach($interp->narrative['nodes'] as $n)
  <tr><td>{{ $n['label'] }}</td><td><span class="pill {{ $n['priority']==='URGENT'?'CRITICAL':($n['priority']==='HIGH'?'SUBOPTIMAL':'OPTIMAL') }}">{{ $n['priority'] }}</span></td><td>{{ $n['note'] }}</td></tr>
@endforeach
</table>

<h2>IFM Order of Repair</h2>
<table><tr><th>Step</th><th>Node</th><th>Priority</th><th>Why first</th></tr>
@foreach($interp->narrative['order_of_repair'] as $o)
  <tr><td>{{ $o['step'] }}</td><td>{{ $o['node'] }}</td><td>{{ $o['priority'] }}</td><td>{{ $o['why'] }}</td></tr>
@endforeach
</table>

<h2>Protocol Pointers (clinician-gated)</h2>
<table><tr><th>Node</th><th>Suggested actions</th></tr>
@foreach($interp->narrative['protocol'] as $p)
  <tr><td>{{ $p['node'] }}</td><td>{{ $p['actions'] }}</td></tr>
@endforeach
</table>
<div class="disc">Narrative sections are auto-derived heuristics for the clinician to review and edit \u2014 not an autonomous diagnosis or prescription. Pharmacology remains a clinician decision.</div>
@endif

<p style="font-size:8px;color:#9aa0a8;text-align:center;">For clinical use only. This report does not replace individualised medical consultation.</p>
</body></html>
