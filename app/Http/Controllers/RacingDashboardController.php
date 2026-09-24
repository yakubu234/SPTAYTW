<?php
namespace App\Http\Controllers;
use App\Models\RacingRace;
use Illuminate\Http\Request;

class RacingDashboardController extends Controller {
 public function index(Request $request){
  $date=$request->string('date')->toString()?:now()->toDateString();
  $races=RacingRace::with(['meeting','runners','analyses.runner'])->whereDate('off_time',$date)->orderBy('off_time')->get();
  $best=$races->mapWithKeys(fn($r)=>[$r->id=>$r->analyses->sortByDesc('score')->first()]);
  $all=$races->flatMap->analyses;
  $summary=[
   'races'=>$races->count(),'runners'=>$races->flatMap->runners->count(),
   'strong'=>$all->where('status','strong')->count(),'candidate'=>$all->where('status','candidate')->count(),
   'qualified'=>$all->whereIn('status',['strong_qualified','qualified'])->count(),
   'watch'=>$all->where('status','watch')->count(),'skip'=>$all->where('status','skip')->count(),
  ];
  $selections=$races->map(fn($r)=>$best[$r->id]??null)->filter(fn($a)=>$a&&in_array($a->status,['strong','candidate','strong_qualified','qualified'],true))->values();
  return view('racing.dashboard',compact('date','races','best','summary','selections'));
 }
}
