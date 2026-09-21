<?php
namespace App\Http\Controllers;
use App\Models\{RacingAnalysis,RacingRace}; use Illuminate\Http\Request;
class RacingDashboardController extends Controller {
 public function index(Request $request){$date=$request->string('date')->toString()?:now()->toDateString();$races=RacingRace::with(['meeting','runners','analyses.runner'])->whereDate('off_time',$date)->orderBy('off_time')->get();$best=$races->mapWithKeys(fn($r)=>[$r->id=>$r->analyses->sortByDesc('score')->first()]);return view('racing.dashboard',compact('date','races','best'));}
}