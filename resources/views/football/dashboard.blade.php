@extends('football.layout')
@section('title','Daily Screening')
@section('content')
<h1>Daily Screening</h1>
<p class="muted">Engineering scores are screening scores, not probabilities. The table shows only the highest-ranked market per fixture; use Details to audit its evidence. Low data quality and high-variance competitions should be treated cautiously.</p>
<div class="card"><form method="GET" class="row"><label>Date<input type="date" name="date" value="{{ $date->toDateString() }}"></label><button class="btn">View date</button></form></div>
<div class="grid">
@foreach(['fixtures'=>'Fixtures','strong'=>'Strong Qualified','qualified'=>'Qualified','watch'=>'Watch','skip'=>'Skip'] as $key=>$label)
<div class="card"><div class="muted">{{ $label }}</div><div class="stat">{{ $stats[$key] ?? 0 }}</div></div>
@endforeach
</div>
<div class="card"><h2>Sync & analyse</h2><form method="POST" action="{{ route('football.refresh') }}" class="row">@csrf<input type="hidden" name="date" value="{{ $date->toDateString() }}"><button class="btn">Sync fixtures & analyse</button></form></div>
<div class="card"><h2>Build shortlist ticket</h2><p class="muted">Keep this for paper-testing until observed grading/calibration is sufficient.</p><form method="POST" action="{{ route('football.tickets.build') }}" class="row">@csrf<input type="hidden" name="date" value="{{ $date->toDateString() }}"><label>Minimum score<input type="number" name="minimum_score" value="85" min="0" max="100"></label><label>Minimum DQ<input type="number" name="minimum_data_quality" value="80" min="0" max="100"></label><label>Max selections<input type="number" name="maximum_selections" value="6" min="1" max="20"></label><label>Max / competition<input type="number" name="maximum_per_competition" value="2" min="1" max="10"></label><button class="btn">Build paper-test ticket</button></form></div>
<div class="card"><h2>Best market per fixture</h2><div class="scroll"><table><thead><tr><th>Fixture</th><th>Competition</th><th>Market</th><th>Score</th><th>DQ</th><th>Status</th><th>Main risk</th><th></th></tr></thead><tbody>
@forelse($latest as $a)<tr><td>{{ $a->fixture->homeTeam->name ?? 'Home' }} vs {{ $a->fixture->awayTeam->name ?? 'Away' }}</td><td>{{ $a->fixture->competition->name ?? '—' }}</td><td>{{ $a->market_type }}<br><span class="muted">{{ $a->selection }}</span></td><td>{{ $a->score }}</td><td>{{ $a->data_quality_score }}</td><td><span class="badge {{ $a->status }}">{{ str_replace('_',' ',strtoupper($a->status)) }}</span></td><td class="risk">{{ $a->main_risk ?: '—' }}</td><td><a href="{{ route('football.analysis.show',$a) }}">Audit evidence</a></td></tr>@empty<tr><td colspan="8">No analyses for this date yet.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
