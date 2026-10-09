<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Fixture extends Model { protected $fillable=['provider_id','competition_id','home_team_id','away_team_id','kickoff_at','status','home_goals','away_goals','home_corners','away_corners','corner_stats_checked_at']; protected $casts=['kickoff_at'=>'datetime','corner_stats_checked_at'=>'datetime']; public function analyses(){return $this->hasMany(MarketAnalysis::class);} public function competition(){return $this->belongsTo(Competition::class);} public function homeTeam(){return $this->belongsTo(Team::class,'home_team_id');} public function awayTeam(){return $this->belongsTo(Team::class,'away_team_id');} }
