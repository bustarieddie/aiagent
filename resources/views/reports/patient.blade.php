<!DOCTYPE html><html><head><meta charset="utf-8">@include('reports._style')</head><body>
<div class="cover">
  <div style="letter-spacing:2px;color:#1f6f5c;font-size:8px;font-weight:bold;">FUNCTIONAL MEDICINE · CLINICAL INTERPRETATION</div>
  <h1>Functional Medicine Clinical Interpretation Report</h1>
  <div class="sub">Patient Edition — Your Health, Your Journey</div>
</div>

<table class="meta">
  <tr><th>Patient</th><td>{{ $patient->name }}</td><th>Sex / Age</th><td>{{ $patient->sex }} / {{ $patient->age }}</td></tr>
  <tr><th>Lab No.</th><td>{{ $report->lab_no }}</td><th>Report Date</th><td>{{ optional($report->reported_at)->format('d M Y') }}</td></tr>
</table>

@if($interp && count($interp->critical ?? []))
<h2>Priority Findings</h2>
<table><tr><th>#</th><th>Marker</th><th>Your Result</th><th>Status</th></tr>
@foreach($interp->critical as $i => $c)
  <tr><td>{{ $i+1 }}</td><td>{{ $c['label'] }}</td><td>{{ $c['value'] }} {{ $c['unit'] }}</td>
      <td><span class="pill {{ $c['status'] }}">{{ $c['status'] }}</span></td></tr>
@endforeach
</table>
<p>These are the items your doctor will want to act on first. Please review them together with Dr. Bustari.</p>
@endif

<h2>What Your Blood Tests Show</h2>
<table><tr><th>What We Measured</th><th>Your Result</th><th>Status</th></tr>
@foreach($results as $r)
  @php $def = \App\Support\FunctionalRanges::definition($r->marker_key); @endphp
  <tr><td>{{ $def['label'] ?? $r->marker_key }}</td>
      <td>{{ $r->value }} {{ $r->unit }}</td>
      <td><span class="pill {{ $r->status }}">{{ $r->status }}</span></td></tr>
@endforeach
</table>

<div class="disc"><strong>DISCLAIMER:</strong> {{ $disclaimer }}</div>
<p style="font-size:8px;color:#9aa0a8;text-align:center;">For clinical use only. This report does not replace individualised medical consultation.</p>
</body></html>
