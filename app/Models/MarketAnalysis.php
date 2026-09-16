<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MarketAnalysis extends Model { protected $fillable=['fixture_id','market_type','selection','score','manual_odds','implied_probability','data_quality_score','status','positive_signals','contradictions','main_risk','analysed_at','result','graded_at']; protected $casts=['positive_signals'=>'array','contradictions'=>'array','analysed_at'=>'datetime','graded_at'=>'datetime']; public function fixture(){return $this->belongsTo(Fixture::class);} public function tickets(){return $this->belongsToMany(Ticket::class,'ticket_selections')->withTimestamps();} }
