<?php
namespace App\Http\Controllers;
use App\Models\{Fixture,MarketAnalysis,Ticket};
use App\Services\Football\Tickets\{TicketBuilder,TicketRiskService};
use App\Services\Football\PerformanceCalibrationService;
use App\Services\Football\{FixtureImporter,FixtureAnalysisService};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
class FootballDashboardController extends Controller {
    public function index(Request $request){
        $date=Carbon::parse($request->string('date')->toString() ?: now()->toDateString());
        $analyses=MarketAnalysis::with(['fixture.competition','fixture.homeTeam','fixture.awayTeam'])->whereHas('fixture',fn($q)=>$q->whereDate('kickoff_at',$date->toDateString()))->orderByDesc('score')->orderByDesc('data_quality_score')->get();
        // The dashboard is a fixture shortlist: show only the highest-ranked market for each fixture.
        // All other markets remain available through their stored analyses and detail links.
        $latest=$analyses->groupBy('fixture_id')->map->first()->sortByDesc('score')->values();
        $stats=['fixtures'=>Fixture::whereDate('kickoff_at',$date)->count(),'strong'=>$latest->where('status','strong_qualified')->count(),'qualified'=>$latest->where('status','qualified')->count(),'watch'=>$latest->where('status','watch')->count(),'skip'=>$latest->where('status','skip')->count()]; return view('football.dashboard',compact('date','latest','stats'));
    }
    public function refresh(Request $request,FixtureImporter $importer,FixtureAnalysisService $service){$data=$request->validate(['date'=>'required|date']);$date=Carbon::parse($data['date']);$count=$importer->importDate($date->toDateString());$fixtures=Fixture::with(['homeTeam','awayTeam','competition'])->whereDate('kickoff_at',$date)->get();$analysed=0;foreach($fixtures as $fixture){if(in_array($fixture->status,['FT','AET','PEN','CANC','PST'],true))continue;$analysed+=count($service->analyse($fixture));}return redirect()->route('football.dashboard',['date'=>$date->toDateString()])->with('message',"Synced {$count} fixtures and generated {$analysed} market analyses.");}
    public function show(MarketAnalysis $analysis){$analysis->load(['fixture.competition','fixture.homeTeam','fixture.awayTeam']);return view('football.analysis',compact('analysis'));}
    public function buildTicket(Request $request,TicketBuilder $builder){$data=$request->validate(['date'=>'required|date','minimum_score'=>'integer|min:0|max:100','minimum_data_quality'=>'integer|min:0|max:100','maximum_selections'=>'integer|min:1|max:20','maximum_per_competition'=>'integer|min:1|max:10','allowed_markets'=>'array']);$data['exclude_high_variance']=$request->boolean('exclude_high_variance',true);$ticket=$builder->build(Carbon::parse($data['date']),$data);return redirect()->route('football.tickets.show',$ticket);}
    public function ticket(Ticket $ticket){$ticket->load('selections.fixture.competition','selections.fixture.homeTeam','selections.fixture.awayTeam');return view('football.ticket',compact('ticket'));}
    public function performance(Request $request){$days=max(1,(int)$request->integer('days',30));$from=now()->subDays($days);$rows=MarketAnalysis::query()->whereNotNull('result')->where('graded_at','>=',$from)->get()->groupBy('market_type')->map(function($items){$graded=$items->count();$won=$items->where('result','won')->count();return ['graded'=>$graded,'won'=>$won,'lost'=>$items->where('result','lost')->count(),'rate'=>$graded?round($won/$graded*100,1):0];});return view('football.performance',compact('rows','days'));}
    public function updateOdds(Request $request,MarketAnalysis $analysis){$data=$request->validate(['manual_odds'=>'nullable|numeric|min:1.01|max:1000']);$odd=$data['manual_odds']??null;$analysis->update(['manual_odds'=>$odd,'implied_probability'=>$odd?round(1/(float)$odd,4):null]);return back()->with('message','Manual odds updated. Implied probability is descriptive bookmaker math, not model confidence.');}
    public function updateTicketRisk(Request $request,Ticket $ticket,TicketRiskService $risk){$data=$request->validate(['bankroll'=>'nullable|numeric|min:0','stake_amount'=>'nullable|numeric|min:0']);$bankroll=$data['bankroll']??null;$stake=$data['stake_amount']??null;$pct=($bankroll&&$stake)?round($stake/$bankroll*100,3):null;$ticket->update(['bankroll'=>$bankroll,'stake_amount'=>$stake,'stake_percent'=>$pct]);$risk->recalculate($ticket);return back()->with('message','Risk figures updated.');}
    public function ticketHistory(){$tickets=Ticket::withCount('selections')->latest()->paginate(30);return view('football.tickets',compact('tickets'));}
    public function calibration(Request $request,PerformanceCalibrationService $service){$days=max(1,(int)$request->integer('days',90));$rows=$service->report($days);return view('football.calibration',compact('rows','days'));}
}
