@extends('football.layout')
@section('title','Performance')
@section('content')
<h1>Observed Performance</h1><p class="muted">Historical results describe observed performance only; they do not guarantee future outcomes.</p>
<div class="card"><form method="GET" class="row"><label>Days<input type="number" name="days" min="1" value="{{ $days }}"></label><button class="btn">Refresh</button></form></div>
<div class="card"><div class="scroll"><table><thead><tr><th>Market</th><th>Graded</th><th>Won</th><th>Lost</th><th>Observed win rate</th></tr></thead><tbody>@forelse($rows as $market=>$row)<tr><td>{{ $market }}</td><td>{{ $row['graded'] }}</td><td>{{ $row['won'] }}</td><td>{{ $row['lost'] }}</td><td>{{ $row['rate'] }}%</td></tr>@empty<tr><td colspan="5">No graded analyses in this period.</td></tr>@endforelse</tbody></table></div></div>
@endsection
