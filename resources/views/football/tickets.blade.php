@extends('football.layout')
@section('title','Tickets')
@section('content')
<h1>Ticket History</h1>
<div class="card"><div class="scroll"><table><thead><tr><th>Date</th><th>Name</th><th>Selections</th><th>Minimum score</th><th>Minimum DQ</th><th>Result</th><th></th></tr></thead><tbody>
@forelse($tickets as $ticket)<tr><td>{{ optional($ticket->fixture_date)->format('Y-m-d') }}</td><td>{{ $ticket->name }}</td><td>{{ $ticket->selections_count }}</td><td>{{ $ticket->minimum_score }}</td><td>{{ $ticket->minimum_data_quality }}</td><td>{{ $ticket->result ?: 'Pending' }}</td><td><a href="{{ route('football.tickets.show',$ticket) }}">Open</a></td></tr>@empty<tr><td colspan="7">No tickets have been built yet.</td></tr>@endforelse
</tbody></table></div></div>
{{ $tickets->links() }}
@endsection
