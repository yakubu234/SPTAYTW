<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Ticket extends Model { protected $fillable=['name','fixture_date','minimum_score','minimum_data_quality','maximum_selections','maximum_per_competition','allowed_markets','excluded_competition_types','bankroll','stake_amount','stake_percent','combined_odds','potential_return','result','graded_at']; protected $casts=['fixture_date'=>'date','allowed_markets'=>'array','excluded_competition_types'=>'array','graded_at'=>'datetime']; public function selections(){return $this->belongsToMany(MarketAnalysis::class,'ticket_selections')->withPivot('position')->withTimestamps()->orderByPivot('position');} }
