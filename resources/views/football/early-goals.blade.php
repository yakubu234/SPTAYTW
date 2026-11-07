@extends('football.layout')
@section('title','First 5 Minutes Goals')
@section('content')
<h1>First 5 Minutes Goals</h1>
<p class="muted">Kickoff times use the app timezone (Nigeria/WAT). Counts are from complete provider event timelines. WATCH means the recent sample meets a screening rule; it does not guarantee a result. SKIP may mean insufficient history or conflicting evidence. Scores are not probabilities.</p>
<div class="card"><form method="GET" action="{{ route('football.early-goals') }}" class="row">
<label>Date<input type="date" name="date" value="{{ $date->toDateString() }}"></label>
<label>Selection<select name="side"><option value="goal_first_5_no" @selected($side === 'goal_first_5_no')>No goal in first 5 minutes</option><option value="goal_first_5" @selected($side === 'goal_first_5')>Goal in first 5 minutes</option></select></label>
<button class="btn">View analyses</button></form></div>
<div class="grid"><div class="card"><div class="muted">No goal analyses</div><div class="stat">{{ $counts['no'] }}</div></div><div class="card"><div class="muted">Goal analyses</div><div class="stat">{{ $counts['yes'] }}</div></div><div class="card"><div class="muted">Watch in this view</div><div class="stat">{{ $counts['watch'] }}</div></div><div class="card"><div class="muted">Skip in this view</div><div class="stat">{{ $counts['skip'] }}</div></div></div>
<div class="card"><div class="scroll"><table><thead><tr><th>Kickoff (WAT)</th><th>Fixture</th><th>League</th><th>Selection</th><th>Score / DQ</th><th>Status</th><th>Recent history</th><th></th></tr></thead><tbody>
@forelse($analyses as $a)
<tr><td>{{ $a->fixture->kickoff_at->format('d M H:i') }}</td><td>{{ $a->fixture->homeTeam->name ?? 'Home' }} vs {{ $a->fixture->awayTeam->name ?? 'Away' }}</td><td>{{ $a->fixture->competition->name ?? '—' }}</td><td>{{ $a->selection }}</td><td>{{ $a->score }} / {{ $a->data_quality_score }}</td><td><span class="badge {{ $a->status }}">{{ strtoupper($a->status) }}</span></td><td>@foreach(($a->positive_signals ?? []) as $signal)<div>{{ $signal }}</div>@endforeach</td><td><a href="{{ route('football.analysis.show',$a) }}">Audit evidence</a></td></tr>
@empty<tr><td colspan="8">No first-five-minute analysis for this date yet. Run football:early-goals for this date after syncing history.</td></tr>
@endforelse
</tbody></table></div></div>
@endsection
