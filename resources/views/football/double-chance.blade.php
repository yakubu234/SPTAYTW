@extends('football.layout')
@section('title', 'Win or Draw Screening')
@section('content')
<style>
    .dc-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
    .dc-table{table-layout:fixed;font-size:13px}
    .dc-table th:nth-child(1){width:12%}.dc-table th:nth-child(2){width:20%}.dc-table th:nth-child(3){width:17%}
    .dc-table th:nth-child(4){width:8%}.dc-table th:nth-child(5){width:8%}.dc-table th:nth-child(6){width:35%}
    .dc-table td{overflow-wrap:anywhere}.dc-table ul{margin:5px 0 0;padding-left:17px}.dc-table li{margin:2px 0}
    .dc-table tr{break-inside:avoid;page-break-inside:avoid}.dc-risk{font-weight:600;color:#991b1b}
    @@media(max-width:780px){.dc-table,.dc-table tbody,.dc-table tr,.dc-table td{display:block;width:100%}.dc-table thead{display:none}.dc-table tr{border-bottom:2px solid #e2e8f0;padding:8px 0}.dc-table td{border:0;padding:4px 0}.dc-table td::before{content:attr(data-label) ': ';font-weight:700;color:#475569}}
    @@media print{@@page{size:A4 landscape;margin:10mm}body{background:#fff;color:#111}.nav,.dc-controls,.dc-print,.alert{display:none!important}.wrap{max-width:none;padding:0}.card{padding:0;border:0;margin:0}.scroll{overflow:visible}.dc-table{font-size:10px}.dc-table th,.dc-table td{padding:5px;vertical-align:top}.dc-table tr{break-inside:avoid;page-break-inside:avoid}.dc-table ul{padding-left:13px}.dc-table td::before{display:none}a{color:inherit;text-decoration:none}.badge{border:1px solid #aaa;background:#fff!important;color:#111!important}h1{font-size:19px}}
</style>
<div class="dc-head">
    <div>
        <h1>Win or Draw Screening — {{ $date->format('d M Y') }}</h1>
        <p class="muted">1X = home team wins or draws. X2 = away team wins or draws. Both sides are shown; score is an engineering screen, not a probability. This market is being paper tested until graded and calibrated.</p>
    </div>
    <button class="btn dc-print" type="button" onclick="window.print()">Print / save PDF</button>
</div>
<div class="card dc-controls">
    <form method="GET" class="row" action="{{ route('football.double-chance') }}">
        <label>Date <input type="date" name="date" value="{{ $date->toDateString() }}"></label>
        <label>Status <select name="status"><option value="">All statuses</option>@foreach(['strong_qualified'=>'Strong Qualified','qualified'=>'Qualified','watch'=>'Watch','skip'=>'Skip'] as $value=>$label)<option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>@endforeach</select></label>
        <label>Side <select name="side"><option value="">Both sides</option><option value="home_or_draw" @selected($side==='home_or_draw')>Home or draw (1X)</option><option value="away_or_draw" @selected($side==='away_or_draw')>Away or draw (X2)</option></select></label>
        <button class="btn" type="submit">Apply</button>
        <a class="btn secondary" href="{{ route('football.double-chance',['date'=>$date->toDateString()]) }}">Show all</a>
    </form>
</div>
<p><strong>{{ $analyses->count() }} displayed</strong> · All win-or-draw analyses on this date: Strong {{ $counts['strong_qualified'] ?? 0 }}, Qualified {{ $counts['qualified'] ?? 0 }}, Watch {{ $counts['watch'] ?? 0 }}, Skip {{ $counts['skip'] ?? 0 }}.</p>
<p class="muted">Kickoff uses the stored fixture time; verify the displayed time against the bookmaker in WAT before selecting. Print applies the filters currently shown above.</p>
<div class="card"><div class="scroll"><table class="dc-table"><thead><tr><th>Kickoff / competition</th><th>Fixture</th><th>Selection</th><th>Score / DQ</th><th>Status</th><th>Evidence and risk</th></tr></thead><tbody>
@forelse($analyses as $analysis)
<tr>
    <td data-label="Kickoff / competition">{{ $analysis->fixture->kickoff_at?->format('d M H:i') }}<br>{{ $analysis->fixture->competition->name ?? '—' }}</td>
    <td data-label="Fixture">{{ $analysis->fixture->homeTeam->name ?? 'Home' }} vs {{ $analysis->fixture->awayTeam->name ?? 'Away' }}</td>
    <td data-label="Selection"><strong>{{ $analysis->market_type==='home_or_draw'?'1X':'X2' }} · {{ $analysis->selection }}</strong><br><a href="{{ route('football.analysis.show',$analysis) }}">Full evidence</a></td>
    <td data-label="Score / DQ">{{ $analysis->score }} / {{ $analysis->data_quality_score }}</td>
    <td data-label="Status"><span class="badge {{ $analysis->status }}">{{ str_replace('_',' ',strtoupper($analysis->status)) }}</span></td>
    <td data-label="Evidence and risk">
        @if($analysis->positive_signals)<ul>@foreach($analysis->positive_signals as $signal)<li>{{ $signal }}</li>@endforeach</ul>@endif
        @if($analysis->contradictions)<ul class="dc-risk">@foreach($analysis->contradictions as $signal)<li>{{ $signal }}</li>@endforeach</ul>@else<p class="dc-risk">{{ $analysis->main_risk }}</p>@endif
    </td>
</tr>
@empty<tr><td colspan="6">No win-or-draw analyses match these filters. Sync and analyse this date first.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
