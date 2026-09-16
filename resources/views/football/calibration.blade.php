@extends('football.layout')
@section('title','Calibration')
@section('content')
<h1>Score Calibration</h1><p class="muted">Engineering score bands are compared with observed outcomes. A band needs at least 50 graded samples before it is marked usable.</p>
<div class="card"><form method="GET" class="row"><label>Days<input type="number" name="days" min="1" value="{{ $days }}"></label><button class="btn">Refresh</button></form></div>
<div class="card"><div class="scroll"><table><thead><tr><th>Market</th><th>Score band</th><th>Samples</th><th>Won</th><th>Lost</th><th>Observed rate</th><th>Sample status</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row['market'] }}</td><td>{{ $row['score_band'] }}</td><td>{{ $row['samples'] }}</td><td>{{ $row['won'] }}</td><td>{{ $row['lost'] }}</td><td>{{ $row['observed_rate'] }}%</td><td><span class="badge">{{ $row['sample_status'] }}</span></td></tr>@empty<tr><td colspan="7">No graded samples available yet.</td></tr>@endforelse</tbody></table></div></div>
@endsection
