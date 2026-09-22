<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RacingAnalysis extends Model {
    protected $fillable=['race_id','runner_id','win_probability','place_probability','fair_odds','market_probability','edge_points','score','data_quality','race_confidence','status','evidence','actual_finish','win_hit','place_hit'];
    protected $casts=['evidence'=>'array','win_hit'=>'boolean','place_hit'=>'boolean'];
    public function race(){return $this->belongsTo(RacingRace::class,'race_id');}
    public function runner(){return $this->belongsTo(RacingRunner::class,'runner_id');}
}